<?php

namespace App\Controller;

use App\Entity\Commande;
use App\Message\OrderPaidMessage;
use Doctrine\ORM\EntityManagerInterface;
use Stripe\Webhook;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;

class StripeWebhookController extends AbstractController
{
    #[Route('/stripe/webhook', name: 'stripe_webhook', methods: ['POST'])]
    public function webhook(
        Request $request,
        EntityManagerInterface $em,
        MessageBusInterface $bus
    ): Response {
        $payload = $request->getContent();
        $sigHeader = $request->headers->get('stripe-signature');

        $secret = $_ENV['STRIPE_WEBHOOK_SECRET'] ?? null;

        if (!$secret) {
            return new Response('Webhook secret missing', 500);
        }

        try {
            $event = Webhook::constructEvent($payload, $sigHeader, $secret);
        } catch (\Throwable $e) {
            return new Response('Invalid signature', 400);
        }

        // Ancien flux Stripe Checkout (checkout.session.completed)
        if ($event->type === 'checkout.session.completed') {

            /** @var \Stripe\Checkout\Session $session */
            $session = $event->data->object;
            $commandeId = $session->metadata->commande_id ?? null;

            if ($commandeId) {
                $commande = $em->getRepository(Commande::class)
                    ->find((int) $commandeId);

                if ($commande && $commande->getStatut() !== Commande::STATUT_PAYEE) {

                    $commande->setStatut(Commande::STATUT_PAYEE);
                    $commande->setPaidAt(new \DateTimeImmutable());

                    // Decrement stock safely
                    foreach ($commande->getItems() as $item) {
                        $produit = $item->getProduit();

                        if (
                            method_exists($produit, 'getQuantiteStock') &&
                            method_exists($produit, 'setQuantiteStock')
                        ) {

                            $stock = (int) $produit->getQuantiteStock();
                            $qty = (int) $item->getQuantite();

                            if ($qty > $stock) {
                                $commande->setStatut(Commande::STATUT_ANNULEE);
                                $em->flush();
                                return new Response('Stock error', 200);
                            }

                            $produit->setQuantiteStock($stock - $qty);
                        }
                    }

                    if (!empty($session->payment_intent)) {
                        $commande->setPaymentRef($session->payment_intent);
                    }

                    $em->flush();

                    // Async email + PDF
                    $bus->dispatch(new OrderPaidMessage($commande->getId()));
                }
            }
        }

        // Nouveau flux Payment Element (payment_intent.succeeded)
        if ($event->type === 'payment_intent.succeeded') {
            /** @var \Stripe\PaymentIntent $pi */
            $pi = $event->data->object;
            $commandeId = $pi->metadata->commande_id ?? null;

            if ($commandeId) {
                $commande = $em->getRepository(Commande::class)
                    ->find((int) $commandeId);

                if ($commande && $commande->getStatut() !== Commande::STATUT_PAYEE) {
                    $commande->setStatut(Commande::STATUT_PAYEE);
                    $commande->setPaidAt(new \DateTimeImmutable());

                    foreach ($commande->getItems() as $item) {
                        $produit = $item->getProduit();

                        if (
                            method_exists($produit, 'getQuantiteStock') &&
                            method_exists($produit, 'setQuantiteStock')
                        ) {
                            $stock = (int) $produit->getQuantiteStock();
                            $qty = (int) $item->getQuantite();

                            if ($qty > $stock) {
                                $commande->setStatut(Commande::STATUT_ANNULEE);
                                $em->flush();
                                return new Response('Stock error', 200);
                            }

                            $produit->setQuantiteStock($stock - $qty);
                        }
                    }

                    // Sauvegarder la référence du paiement (PaymentIntent id)
                    $commande->setPaymentRef($pi->id);
                    $em->flush();

                    // Async email + PDF
                    $bus->dispatch(new OrderPaidMessage($commande->getId()));
                }
            }
        }

        return new Response('ok', 200);
    }
}
