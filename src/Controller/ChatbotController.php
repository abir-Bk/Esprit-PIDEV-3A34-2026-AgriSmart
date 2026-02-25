<?php

namespace App\Controller;

use App\Exception\AiProviderException;
use App\Repository\ProduitRepository;
use App\Service\HuggingFaceService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ChatbotController extends AbstractController
{
    /**
     * AJAX endpoint: receive conversation messages, return AI reply.
     */
    #[Route('/chatbot/message', name: 'app_chatbot_message', methods: ['POST'])]
    public function message(
        Request $request,
        HuggingFaceService $huggingFaceService,
        ProduitRepository $produitRepository
    ): JsonResponse {
        try {
            $data = json_decode($request->getContent(), true);
            if (!\is_array($data)) {
                return $this->json(['error' => 'Requête invalide (JSON attendu).'], 400);
            }

            if (!isset($data['messages']) || !is_array($data['messages'])) {
                return $this->json(['error' => 'Paramètre messages manquant.'], 400);
            }

            $messages = array_slice($data['messages'], -10);

            foreach ($messages as $msg) {
                if (
                    !isset($msg['role'], $msg['content'])
                    || !in_array($msg['role'], ['user', 'assistant'], true)
                    || !is_string($msg['content'])
                ) {
                    return $this->json(['error' => 'Format de message invalide.'], 400);
                }
            }

            $catalog = $this->buildCatalog($produitRepository);

            $reply = $huggingFaceService->chat($messages, $catalog);
            return $this->json(['reply' => $reply]);
        } catch (AiProviderException $e) {
            return $this->json([
                'error' => $e->getUserMessage(),
                'provider' => $e->getProvider(),
                'kind' => $e->getKind(),
            ], $e->getStatusCode());
        } catch (\RuntimeException $e) {
            return $this->json(['error' => $e->getMessage()], 503);
        } catch (\Throwable $e) {
            return $this->json(['error' => 'Erreur serveur : ' . $e->getMessage()], 500);
        }
    }

    /**
     * Fetch up to 35 in-stock products and format them as a concise catalog string.
     * Limité pour rester sous les quotas IA (tokens/requête) et éviter 429.
     */
    private function buildCatalog(ProduitRepository $repo): string
    {
        $produits = $repo->createQueryBuilder('p')
            ->where('p.quantiteStock > 0')
            ->orderBy('p.categorie', 'ASC')
            ->addOrderBy('p.nom', 'ASC')
            ->setMaxResults(35)
            ->getQuery()
            ->getResult();

        if (empty($produits)) {
            return 'Aucun produit disponible en stock pour le moment.';
        }

        $lines = [];
        foreach ($produits as $p) {
            $price = $p->isPromotion() && $p->getPromotionPrice()
                ? $p->getPromotionPrice() . ' TND (promo, était ' . $p->getPrix() . ' TND)'
                : $p->getPrix() . ' TND';

            $productLink = $p->getId() !== null
                ? $this->generateUrl('app_produit_show', ['id' => $p->getId()], UrlGeneratorInterface::ABSOLUTE_URL)
                : '#';

            $lines[] = sprintf(
                '- %s | Catégorie: %s | Prix: %s | Stock: %d unité(s)%s | Lien: %s',
                $p->getNom(),
                $p->getCategorie() ?? 'N/A',
                $price,
                $p->getQuantiteStock(),
                $p->getDescription() ? ' | ' . mb_substr($p->getDescription(), 0, 50) . '…' : '',
                $productLink
            );
        }

        return implode("\n", $lines);
    }
}
