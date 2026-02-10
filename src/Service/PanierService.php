<?php

namespace App\Service;

use App\Repository\ProduitRepository;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class PanierService
{
    private SessionInterface $session;
    private ProduitRepository $produitRepository;

    private const SESSION_KEY = 'panier';

    public function __construct(RequestStack $requestStack, ProduitRepository $produitRepository)
    {
        $this->session = $requestStack->getSession();
        $this->produitRepository = $produitRepository;
    }

    /** @return array<int,int> id => qty */
    public function getCart(): array
    {
        return $this->session->get(self::SESSION_KEY, []);
    }

    private function saveCart(array $cart): void
    {
        $this->session->set(self::SESSION_KEY, $cart);
    }

    public function add(int $produitId, int $qty = 1): void
    {
        $qty = max(1, $qty);
        $cart = $this->getCart();
        $cart[$produitId] = ($cart[$produitId] ?? 0) + $qty;
        $this->saveCart($cart);
    }

    public function removeOne(int $produitId): void
    {
        $cart = $this->getCart();
        if (!isset($cart[$produitId])) {
            return;
        }

        $cart[$produitId]--;
        if ($cart[$produitId] <= 0) {
            unset($cart[$produitId]);
        }

        $this->saveCart($cart);
    }

    public function removeAll(int $produitId): void
    {
        $cart = $this->getCart();
        unset($cart[$produitId]);
        $this->saveCart($cart);
    }

    public function clear(): void
    {
        $this->saveCart([]);
    }

    /** Données prêtes pour Twig */
    public function getDetails(): array
    {
        $cart = $this->getCart();

        $items = [];
        $totalQty = 0;
        $total = 0.0;

        foreach ($cart as $id => $qty) {
            $produit = $this->produitRepository->find((int) $id);
            if (!$produit) {
                continue;
            }

            // ✅ FIX: pas de getIsPromotion() → en général c'est isPromotion()
            $isPromo = method_exists($produit, 'isPromotion') ? (bool) $produit->isPromotion() : (bool) ($produit->getIsPromotion() ?? false);
            $promoPrice = method_exists($produit, 'getPromotionPrice') ? $produit->getPromotionPrice() : null;

            $unitPrice = ($isPromo && $promoPrice !== null) ? (float) $promoPrice : (float) $produit->getPrix();
            $lineTotal = $unitPrice * (int) $qty;

            $items[] = [
                'produit' => $produit,
                'qty' => (int) $qty,
                'unitPrice' => $unitPrice,
                'lineTotal' => $lineTotal,
            ];

            $totalQty += (int) $qty;
            $total += $lineTotal;
        }

        return [
            'items' => $items,
            'totalQty' => $totalQty,
            'total' => $total,
        ];
    }
}
