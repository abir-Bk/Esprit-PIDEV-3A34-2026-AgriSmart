<?php

namespace App\Service;

use App\Entity\Produit;
use App\Entity\User;
use App\Repository\CommandeItemRepository;
use App\Repository\ProduitRepository;
use App\Repository\WishlistItemRepository;
use Symfony\Component\Process\Process;

class MarketplaceRecommendationService
{
    public function __construct(
        private readonly ProduitRepository $produitRepository,
        private readonly WishlistItemRepository $wishlistItemRepository,
        private readonly CommandeItemRepository $commandeItemRepository,
        private readonly PanierService $panierService,
    ) {
    }

    /**
     * @param array{q?:string,type?:string,categorie?:string,promo?:string,sort?:string,page?:int} $filters
     * @return Produit[]
     */
    public function recommendForUser(User $user, array $filters, int $limit = 3): array
    {
        $limit = max(1, $limit);
        $payload = $this->buildPayload($user, $filters);
        if (($payload['candidates'] ?? []) === []) {
            return [];
        }

        $rootDir = dirname(__DIR__, 2);
        $scriptPath = $rootDir . DIRECTORY_SEPARATOR . 'ml' . DIRECTORY_SEPARATOR . 'recommend.py';
        if (!is_file($scriptPath)) {
            return [];
        }

        $tmpFile = tempnam(sys_get_temp_dir(), 'agrismart_ml_');
        if ($tmpFile === false) {
            return [];
        }

        try {
            file_put_contents($tmpFile, json_encode($payload, JSON_THROW_ON_ERROR));

            $pythonBin = $_ENV['PYTHON_BIN'] ?? $_SERVER['PYTHON_BIN'] ?? 'python';
            $process = new Process([$pythonBin, $scriptPath, '--input', $tmpFile, '--top', (string) $limit], $rootDir);
            $process->setTimeout(5);
            $process->run();

            if (!$process->isSuccessful()) {
                return [];
            }

            $decoded = json_decode($process->getOutput(), true);
            if (!is_array($decoded) || !isset($decoded['productIds']) || !is_array($decoded['productIds'])) {
                return [];
            }

            $ids = array_values(array_unique(array_map('intval', $decoded['productIds'])));
            if ($ids === []) {
                return [];
            }

            $products = $this->produitRepository->findBy(['id' => $ids]);
            $byId = [];
            foreach ($products as $product) {
                if ($product instanceof Produit && $product->getId() !== null) {
                    $byId[$product->getId()] = $product;
                }
            }

            $ordered = [];
            foreach ($ids as $id) {
                if (isset($byId[$id])) {
                    $ordered[] = $byId[$id];
                }
            }

            return $ordered;
        } catch (\Throwable) {
            return [];
        } finally {
            @unlink($tmpFile);
        }
    }

    /**
     * @param array{q?:string,type?:string,categorie?:string,promo?:string,sort?:string,page?:int} $filters
     * @return array<string,mixed>
     */
    private function buildPayload(User $user, array $filters): array
    {
        $interactions = [];
        $excludedIds = [];

        foreach ($this->wishlistItemRepository->findProductsByUser($user) as $product) {
            if (!$product instanceof Produit || $product->getId() === null) {
                continue;
            }
            $excludedIds[$product->getId()] = true;
            $interactions[] = [
                'weight' => 2.0,
                'product' => $this->serializeProduct($product),
            ];
        }

        foreach ($this->commandeItemRepository->findPurchasedProductsByUser($user) as $row) {
            $productId = (int) ($row['productId'] ?? 0);
            $qty = max(1, (int) ($row['qty'] ?? 1));
            if ($productId <= 0) {
                continue;
            }

            $product = $this->produitRepository->find($productId);
            if (!$product instanceof Produit || $product->getId() === null) {
                continue;
            }

            $excludedIds[$product->getId()] = true;
            $interactions[] = [
                'weight' => 3.0 * $qty,
                'product' => $this->serializeProduct($product),
            ];
        }

        foreach ($this->panierService->getCart() as $productId => $qty) {
            $product = $this->produitRepository->find((int) $productId);
            if (!$product instanceof Produit || $product->getId() === null) {
                continue;
            }

            $excludedIds[$product->getId()] = true;
            $interactions[] = [
                'weight' => 2.5 * max(1, (int) $qty),
                'product' => $this->serializeProduct($product),
            ];
        }

        $qb = $this->produitRepository->createQueryBuilder('p')
            ->andWhere('p.banned = false')
            ->andWhere('p.quantiteStock > 0')
            ->andWhere('p.vendeur IS NULL OR p.vendeur != :user')
            ->setParameter('user', $user)
            ->setMaxResults(120)
            ->orderBy('p.createdAt', 'DESC');

        if (($filters['type'] ?? '') !== '' && in_array($filters['type'], [Produit::TYPE_VENTE, Produit::TYPE_LOCATION], true)) {
            $qb->andWhere('p.type = :type')->setParameter('type', $filters['type']);
        }
        if (($filters['categorie'] ?? '') !== '') {
            $qb->andWhere('p.categorie = :categorie')->setParameter('categorie', (string) $filters['categorie']);
        }
        if (($filters['promo'] ?? '') === '1') {
            $qb->andWhere('p.isPromotion = true');
        }

        /** @var Produit[] $products */
        $products = $qb->getQuery()->getResult();
        $candidates = [];
        foreach ($products as $product) {
            $id = $product->getId();
            if ($id === null || isset($excludedIds[$id])) {
                continue;
            }
            $candidates[] = $this->serializeProduct($product);
        }

        return [
            'user' => [
                'id' => $user->getId(),
                'address' => (string) ($user->getAddress() ?? ''),
            ],
            'filters' => [
                'q' => (string) ($filters['q'] ?? ''),
                'type' => (string) ($filters['type'] ?? ''),
                'categorie' => (string) ($filters['categorie'] ?? ''),
                'promo' => (string) ($filters['promo'] ?? ''),
            ],
            'interactions' => $interactions,
            'candidates' => $candidates,
        ];
    }

    /**
     * @return array<string,mixed>
     */
    private function serializeProduct(Produit $product): array
    {
        return [
            'id' => $product->getId(),
            'nom' => (string) ($product->getNom() ?? ''),
            'description' => (string) ($product->getDescription() ?? ''),
            'categorie' => (string) ($product->getCategorie() ?? ''),
            'type' => (string) ($product->getType() ?? ''),
            'isPromotion' => (bool) $product->isPromotion(),
            'effectivePrice' => (float) ($product->getPrixEffectif() ?? 0.0),
            'locationAddress' => (string) ($product->getLocationAddress() ?? ''),
            'createdAt' => $product->getCreatedAt()?->format(DATE_ATOM),
        ];
    }
}

