<?php

namespace App\Service;

use App\Repository\ProduitRepository;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class PanierService
{
    private const SESSION_KEY = 'panier'; // [produitId => qty]
    private const SESSION_BOOKING_KEY = 'panier_location_bookings'; // [produitId => ['start' => Y-m-d, 'end' => Y-m-d, 'days' => int]]
    private SessionInterface $session;

    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly ProduitRepository $produitRepository
    ) {
        $this->session = $this->requestStack->getSession();
    }

    /** @return array<int,int> */
    public function getCart(): array
    {
        return $this->session->get(self::SESSION_KEY, []);
    }

    private function saveCart(array $cart): void
    {
        $this->session->set(self::SESSION_KEY, $cart);
    }

    private function getStock(int $produitId): int
    {
        $p = $this->produitRepository->find($produitId);
        if (!$p)
            return 0;

        // adapte si ton getter diffère
        $stock = method_exists($p, 'getQuantiteStock') ? (int) ($p->getQuantiteStock() ?? 0) : 0;
        return max(0, $stock);
    }

    private function isLocationProduct(int $produitId): bool
    {
        $p = $this->produitRepository->find($produitId);
        if (!$p || !method_exists($p, 'getType')) {
            return false;
        }
        return (string) $p->getType() === 'location';
    }

    /** @return array<string,array{start:string,end:string,days:int}> */
    public function getLocationBookings(): array
    {
        return $this->session->get(self::SESSION_BOOKING_KEY, []);
    }

    public function setLocationBooking(int $produitId, string $start, string $end, int $days): void
    {
        $days = max(1, $days);
        $bookings = $this->getLocationBookings();
        $bookings[(string) $produitId] = [
            'start' => $start,
            'end' => $end,
            'days' => $days,
        ];
        $this->session->set(self::SESSION_BOOKING_KEY, $bookings);
    }

    public function removeLocationBooking(int $produitId): void
    {
        $bookings = $this->getLocationBookings();
        unset($bookings[(string) $produitId]);
        $this->session->set(self::SESSION_BOOKING_KEY, $bookings);
    }

    public function add(int $produitId, int $qty = 1): void
    {
        $qty = max(1, $qty);

        if ($this->isLocationProduct($produitId)) {
            $cart = $this->getCart();
            $current = (int) ($cart[$produitId] ?? 0);
            $cart[$produitId] = $current + $qty;
            $this->saveCart($cart);
            return;
        }

        $stock = $this->getStock($produitId);
        if ($stock <= 0)
            return;

        $cart = $this->getCart();
        $current = (int) ($cart[$produitId] ?? 0);
        $newQty = min($stock, $current + $qty);

        $cart[$produitId] = $newQty;
        $this->saveCart($cart);
    }

    public function setQty(int $produitId, int $qty): void
    {
        $qty = max(0, (int) $qty);

        if ($this->isLocationProduct($produitId)) {
            $cart = $this->getCart();
            if ($qty <= 0) {
                unset($cart[$produitId]);
                $this->saveCart($cart);
                $this->removeLocationBooking($produitId);
                return;
            }
            $cart[$produitId] = $qty;
            $this->saveCart($cart);
            return;
        }

        $stock = $this->getStock($produitId);

        $cart = $this->getCart();

        if ($qty <= 0) {
            unset($cart[$produitId]);
            $this->saveCart($cart);
            return;
        }

        if ($stock <= 0) {
            unset($cart[$produitId]);
            $this->saveCart($cart);
            return;
        }

        $cart[$produitId] = min($stock, $qty);
        $this->saveCart($cart);
    }

    public function decrement(int $produitId, int $qty = 1): void
    {
        $qty = max(1, $qty);

        $cart = $this->getCart();
        if (!isset($cart[$produitId]))
            return;

        $cart[$produitId] -= $qty;
        if ($cart[$produitId] <= 0)
            unset($cart[$produitId]);

        $this->saveCart($cart);
    }

    public function remove(int $produitId): void
    {
        $cart = $this->getCart();
        unset($cart[$produitId]);
        $this->saveCart($cart);
        $this->removeLocationBooking($produitId);
    }

    public function clear(): void
    {
        $this->saveCart([]);
        $this->session->set(self::SESSION_BOOKING_KEY, []);
    }

    public function countItems(): int
    {
        $count = 0;
        foreach ($this->getCart() as $qty)
            $count += (int) $qty;
        return $count;
    }

    /**
     * @return array{items: array<int,array{produit: object, qty:int, unitPrice: float, lineTotal: float}>, total: float, count: int}
     */
    public function getDetails(): array
    {
        $cart = $this->getCart();
        $bookings = $this->getLocationBookings();
        $items = [];
        $total = 0.0;

        foreach ($cart as $id => $qty) {
            $produit = $this->produitRepository->find((int) $id);
            if (!$produit)
                continue;

            $isPromo = method_exists($produit, 'isIsPromotion')
                ? (bool) $produit->isIsPromotion()
                : (method_exists($produit, 'isPromotion') ? (bool) $produit->isPromotion() : false);

            $promoPrice = method_exists($produit, 'getPromotionPrice') ? $produit->getPromotionPrice() : null;
            $prix = method_exists($produit, 'getPrix') ? $produit->getPrix() : 0;

            $unitPrice = ($isPromo && $promoPrice !== null) ? (float) $promoPrice : (float) $prix;
            $lineTotal = $unitPrice * (int) $qty;

            $booking = null;
            $isLocation = method_exists($produit, 'getType') && (string) $produit->getType() === 'location';
            if ($isLocation) {
                $booking = $bookings[(string) $id] ?? null;
                if (is_array($booking) && isset($booking['days'])) {
                    $days = max(1, (int) $booking['days']);
                    $qty = $days;
                    $lineTotal = $unitPrice * $days;
                }
            }

            $items[] = [
                'produit' => $produit,
                'qty' => (int) $qty,
                'unitPrice' => $unitPrice,
                'lineTotal' => $lineTotal,
                'booking' => $booking,
            ];

            $total += $lineTotal;
        }

        return [
            'items' => $items,
            'total' => $total,
            'count' => $this->countItems(),
        ];
    }

    // alias utilisé par CheckoutController
    public function getFullCart(): array
    {
        return $this->getDetails();
    }
}
