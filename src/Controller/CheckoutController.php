<?php

namespace App\Controller;

use App\Entity\Commande;
use App\Entity\CommandeItem;
use App\Service\PanierService;
use Doctrine\ORM\EntityManagerInterface;
use Stripe\StripeClient;
use Stripe\Checkout\Session as StripeSession;
use Stripe\Stripe;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('IS_AUTHENTICATED_FULLY')]
class CheckoutController extends AbstractController
{
    // ✅ Test mail (Gmail SMTP / Mailtrap)
    #[Route('/test-mail', name: 'app_test_mail', methods: ['GET'])]
    public function testMail(MailerInterface $mailer): Response
    {
        $fromEmail = $_ENV['MAIL_FROM'] ?? 'ayafdhila@gmail.com';

        $email = (new Email())
            ->from($fromEmail)
            ->to('test@example.com')
            ->subject('TEST MAIL AGRISMART')
            ->text('Hello from Symfony Mailer');

        $mailer->send($email);

        return new Response('MAIL SENT');
    }

    #[Route('/checkout', name: 'app_checkout_index', methods: ['GET'])]
    public function index(PanierService $panier): Response
    {
        $details = $panier->getDetails();

        return $this->render('front/semi-public/checkout/index.html.twig', [
            'items' => $details['items'],
            'total' => $details['total'],
            'count' => $details['count'],
            // clé publique Stripe pour Payment Element (option 2)
            // On lit STRIPE_PUBLISHABLE_KEY défini dans .env.local
            'stripe_public_key' => $_ENV['STRIPE_PUBLISHABLE_KEY'] ?? ($_ENV['STRIPE_PUBLIC_KEY'] ?? null),
        ]);
    }

    /**
     * Création d'un PaymentIntent Stripe pour paiement par carte
     * (utilisé par Stripe Payment Element côté front, sans redirection externe).
     */
    #[Route('/checkout/create-payment-intent', name: 'app_checkout_create_payment_intent', methods: ['POST'])]
    public function createPaymentIntent(
        Request $request,
        PanierService $panier,
        EntityManagerInterface $em,
        StripeClient $stripe
    ): Response {
        if (!$this->isCsrfTokenValid('checkout_submit', (string) $request->request->get('_token'))) {
            return $this->json(['status' => 'error', 'message' => 'Token CSRF invalide.'], 400);
        }

        $user = $this->getUser();
        if (!$user) {
            return $this->json(['status' => 'error', 'message' => 'Vous devez être connecté.'], 401);
        }

        $details = $panier->getDetails();
        $items = $details['items'];
        $total = (float) $details['total'];

        if (count($items) === 0) {
            return $this->json(['status' => 'error', 'message' => 'Votre panier est vide.'], 400);
        }

        $adresseLivraison = trim((string) $request->request->get('customer_address', ''));
        if ($adresseLivraison === '') {
            return $this->json(['status' => 'error', 'message' => 'Adresse de livraison requise.'], 400);
        }

        // Re-check stock
        foreach ($items as $row) {
            $produit = $row['produit'];
            $qty = (int) $row['qty'];

            $stock = $this->readStock($produit);
            if ($stock !== null && $qty > $stock) {
                return $this->json([
                    'status' => 'error',
                    'message' => sprintf(
                        "Stock insuffisant pour '%s' (demandé %d, stock %d).",
                        $produit->getNom(),
                        $qty,
                        $stock
                    ),
                ], 400);
            }
        }

        // Créer la commande en attente, paiement carte
        $commande = new Commande();
        $commande->setClient($user);
        $commande->setAdresseLivraison($adresseLivraison);
        $commande->setModePaiement(Commande::PAIEMENT_CARTE);
        $commande->setStatut(Commande::STATUT_EN_ATTENTE);
        $commande->setMontantTotal($total);

        foreach ($items as $row) {
            $item = new CommandeItem();
            $item->setProduit($row['produit']);
            $item->setQuantite((int) $row['qty']);
            $item->setPrixUnitaire((float) $row['unitPrice']);
            $commande->addItem($item);
        }

        $em->persist($commande);
        $em->flush();

        // Créer le PaymentIntent Stripe (montant en cents)
        $amount = (int) round($total * 100);

        try {
            $paymentIntent = $stripe->paymentIntents->create([
                'amount' => $amount,
                'currency' => 'eur', // adapter si vous utilisez une autre devise
                'metadata' => [
                    'commande_id' => (string) $commande->getId(),
                ],
            ]);
        } catch (\Throwable $e) {
            return $this->json([
                'status' => 'error',
                'message' => 'Erreur Stripe: ' . $e->getMessage(),
            ], 500);
        }

        return $this->json([
            'status' => 'ok',
            'clientSecret' => $paymentIntent->client_secret,
            'commandeId' => $commande->getId(),
        ]);
    }

    #[Route('/checkout/process', name: 'app_checkout_process', methods: ['POST'])]
    public function process(
        Request $request,
        PanierService $panier,
        EntityManagerInterface $em,
        MailerInterface $mailer
    ): Response {
        // CSRF
        if (!$this->isCsrfTokenValid('checkout_submit', (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token');
        }

        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        // Panier
        $details = $panier->getDetails();
        $items = $details['items'];
        $total = (float) $details['total'];

        if (count($items) === 0) {
            $this->addFlash('warning', 'Votre panier est vide.');
            return $this->redirectToRoute('app_panier_index');
        }

        // Form
        $adresseLivraison = trim((string) $request->request->get('customer_address', ''));
        if ($adresseLivraison === '') {
            $this->addFlash('danger', 'Veuillez saisir une adresse de livraison.');
            return $this->redirectToRoute('app_checkout_index');
        }

        $paymentMethod = (string) $request->request->get('payment_method', 'domicile'); // domicile | carte
        if (!in_array($paymentMethod, ['domicile', 'carte'], true)) {
            $paymentMethod = 'domicile';
        }

        // Le paiement carte est désormais géré via Stripe Payment Element en JS,
        // on ne lance plus Stripe Checkout ici pour éviter toute redirection externe.
        if ($paymentMethod === 'carte') {
            $this->addFlash('danger', 'Le paiement par carte doit être effectué via le formulaire intégré.');
            return $this->redirectToRoute('app_checkout_index');
        }

        // Re-check stock (anti multi-tab)
        foreach ($items as $row) {
            $produit = $row['produit'];
            $qty = (int) $row['qty'];

            $stock = $this->readStock($produit);
            if ($stock !== null && $qty > $stock) {
                $this->addFlash(
                    'danger',
                    sprintf("Stock insuffisant pour '%s' (demandé %d, stock %d).", $produit->getNom(), $qty, $stock)
                );
                return $this->redirectToRoute('app_panier_index');
            }
        }

        // Create Commande (ALWAYS en_attente)
        $commande = new Commande();
        $commande->setClient($user);
        $commande->setAdresseLivraison($adresseLivraison);
        $commande->setModePaiement(Commande::PAIEMENT_DOMICILE);
        $commande->setStatut(Commande::STATUT_EN_ATTENTE);
        $commande->setMontantTotal($total);

        foreach ($items as $row) {
            $item = new CommandeItem();
            $item->setProduit($row['produit']);
            $item->setQuantite((int) $row['qty']);
            $item->setPrixUnitaire((float) $row['unitPrice']);
            $commande->addItem($item);
        }

        $em->persist($commande);
        $em->flush();

        // ✅ COD => décrément stock + envoyer mail tout de suite
        if ($paymentMethod === 'domicile') {
            // option : décrément stock tout de suite pour COD (si vous le voulez)
            $this->decrementStockOrFail($commande, $em);

            $this->sendConfirmationEmail($mailer, $commande, $user);
            $panier->clear();

            return $this->redirectToRoute('app_checkout_confirm', ['id' => $commande->getId()]);
        }

        // ✅ CARD => Stripe Checkout (NE PAS envoyer mail ici)
        $stripeKey = $_ENV['STRIPE_SECRET_KEY'] ?? null;
        if (!$stripeKey) {
            $this->addFlash('danger', 'Clé Stripe manquante.');
            return $this->redirectToRoute('app_checkout_index');
        }

        Stripe::setApiKey($stripeKey);

        $lineItems = [];
        foreach ($items as $row) {
            $produit = $row['produit'];
            $lineItems[] = [
                'quantity' => (int) $row['qty'],
                'price_data' => [
                    'currency' => 'eur',
                    'unit_amount' => (int) round(((float) $row['unitPrice']) * 100),
                    'product_data' => [
                        'name' => (string) $produit->getNom(),
                    ],
                ],
            ];
        }

        $baseUrl = rtrim($_ENV['APP_URL'] ?? 'http://127.0.0.1:8000', '/');

        $session = StripeSession::create([
            'mode' => 'payment',
            'line_items' => $lineItems,
            'success_url' => $baseUrl . $this->generateUrl('app_checkout_confirm', ['id' => $commande->getId()]) . '?stripe=success',
            'cancel_url' => $baseUrl . $this->generateUrl('app_checkout_index') . '?stripe=cancel',
            'metadata' => [
                'commande_id' => (string) $commande->getId(),
            ],
        ]);

        // Store session id (ref)
        $commande->setPaymentRef($session->id);
        $em->flush();

        $panier->clear();
        return $this->redirect($session->url);
    }

    #[Route('/checkout/confirm/{id}', name: 'app_checkout_confirm', methods: ['GET'])]
    public function confirm(Commande $commande, Request $request): Response
    {
        if ($commande->getClient() !== $this->getUser() && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException();
        }

        return $this->render('front/semi-public/checkout/confirm.html.twig', [
            'commande' => $commande,
            'stripe' => $request->query->get('stripe'),
        ]);
    }

    private function sendConfirmationEmail(MailerInterface $mailer, Commande $commande, object $user): void
    {
        $toEmail = method_exists($user, 'getEmail') ? $user->getEmail() : $user->getUserIdentifier();
        $fromEmail = $_ENV['MAIL_FROM'] ?? 'ayafdhila@gmail.com';

        $email = (new TemplatedEmail())
            ->from(new Address($fromEmail, 'AgriSmart'))
            ->to((string) $toEmail)
            ->subject('Confirmation de commande #' . $commande->getId())
            ->htmlTemplate('front/semi-public/emails/commande_confirmation.html.twig')
            ->context([
                'commande' => $commande,
                'user' => $user,
            ]);

        $mailer->send($email);
    }

    private function decrementStockOrFail(Commande $commande, EntityManagerInterface $em): void
    {
        foreach ($commande->getItems() as $item) {
            $p = $item->getProduit();
            if (method_exists($p, 'getQuantiteStock') && method_exists($p, 'setQuantiteStock')) {
                $stock = (int) ($p->getQuantiteStock() ?? 0);
                $need = (int) $item->getQuantite();
                if ($need > $stock) {
                    $commande->setStatut(Commande::STATUT_ANNULEE);
                    $em->flush();
                    throw $this->createAccessDeniedException('Stock insuffisant.');
                }
                $p->setQuantiteStock($stock - $need);
            }
        }
        $em->flush();
    }

    private function readStock(object $produit): ?int
    {
        if (method_exists($produit, 'getQuantiteStock'))
            return (int) ($produit->getQuantiteStock() ?? 0);
        if (method_exists($produit, 'getStock'))
            return (int) ($produit->getStock() ?? 0);
        if (method_exists($produit, 'getQuantite'))
            return (int) ($produit->getQuantite() ?? 0);
        return null;
    }
}
