<?php

namespace App\Controller;

use App\Entity\Commande;
use App\Entity\CommandeItem;
use App\Service\PanierService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Annotation\Route;

class CheckoutController extends AbstractController
{
    #[Route('/checkout', name: 'app_checkout_index', methods: ['GET'])]
    public function index(PanierService $panier): Response
    {
        // IMPORTANT: utiliser getDetails() (pas getCart())
        $details = $panier->getDetails();

        return $this->render('front/checkout/index.html.twig', [
            'items' => $details['items'],
            'total' => $details['total'],
            'count' => $details['count'],
        ]);
    }

    #[Route('/checkout/process', name: 'app_checkout_process', methods: ['POST'])]
    public function process(
        Request $request,
        PanierService $panier,
        EntityManagerInterface $em,
        MailerInterface $mailer
    ): Response {
        // 1) CSRF
        if (!$this->isCsrfTokenValid('checkout_submit', (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token');
        }

        // 2) Cart
        $details = $panier->getDetails();
        $items = $details['items'];
        $total = (float) $details['total'];

        if (count($items) === 0) {
            $this->addFlash('warning', 'Votre panier est vide.');
            return $this->redirectToRoute('app_panier_index');
        }

        // 3) User obligatoire (car Commande->client NOT NULL)
        $user = $this->getUser();
        if (!$user) {
            $this->addFlash('danger', 'Veuillez vous connecter pour passer commande.');
            return $this->redirectToRoute('app_login'); // adapte si ta route login est différente
        }

        // 4) Champs formulaire
        $adresseLivraison = trim((string) $request->request->get('customer_address', ''));
        if ($adresseLivraison === '') {
            $this->addFlash('danger', 'Veuillez saisir une adresse de livraison.');
            return $this->redirectToRoute('app_checkout_index');
        }

        $paymentMethod = (string) $request->request->get('payment_method', 'domicile'); // domicile | carte

        // 5) Contrôle stock (si ton Produit a getStock() ou getQuantiteStock())
        foreach ($items as $row) {
            $produit = $row['produit'];
            $qty = (int) $row['qty'];

            $stock = null;
            if (method_exists($produit, 'getStock')) {
                $stock = (int) $produit->getStock();
            } elseif (method_exists($produit, 'getQuantiteStock')) {
                $stock = (int) $produit->getQuantiteStock();
            } elseif (method_exists($produit, 'getQuantite')) {
                $stock = (int) $produit->getQuantite();
            }

            if ($stock !== null && $qty > $stock) {
                $this->addFlash(
                    'danger',
                    sprintf("Stock insuffisant pour '%s' (demandé: %d, stock: %d).", $produit->getNom(), $qty, $stock)
                );
                return $this->redirectToRoute('app_panier_index');
            }
        }

        // 6) Créer Commande (mapping EXACT sur ton Entity)
        $commande = new Commande();
        $commande->setClient($user);
        $commande->setAdresseLivraison($adresseLivraison);

        // modePaiement (carte|domicile)
        $commande->setModePaiement(
            $paymentMethod === 'carte' ? Commande::PAIEMENT_CARTE : Commande::PAIEMENT_DOMICILE
        );

        // statut
        // (DEMO) si carte => payée directement, sinon en attente
        $commande->setStatut(
            $paymentMethod === 'carte' ? Commande::STATUT_PAYEE : Commande::STATUT_EN_ATTENTE
        );

        // total snapshot
        $commande->setMontantTotal($total);

        // ref paiement (DEMO)
        if ($paymentMethod === 'carte') {
            $commande->setPaymentRef('DEMO-' . strtoupper(bin2hex(random_bytes(4))));
        }

        // 7) Items
        foreach ($items as $row) {
            $item = new CommandeItem();
            $item->setProduit($row['produit']);
            $item->setQuantite((int) $row['qty']);
            $item->setPrixUnitaire((float) $row['unitPrice']);

            $commande->addItem($item); // lie commande + cascade persist OK
        }

        $em->persist($commande);
        $em->flush();

        // 8) Vider panier
        $panier->clear();

        // 9) Email confirmation (simple + personnalisé)
        // Adapte l'email user: getEmail() / getUserIdentifier() selon ton User entity
        $toEmail = method_exists($user, 'getEmail') ? $user->getEmail() : $user->getUserIdentifier();

        $email = (new TemplatedEmail())
            ->from(new Address('no-reply@agrismart.tn', 'AgriSmart'))
            ->to((string) $toEmail)
            ->subject('Confirmation de commande #' . $commande->getId())
            ->htmlTemplate('emails/commande_confirmation.html.twig')
            ->context([
                'commande' => $commande,
                'user' => $user,
            ]);

        try {
            $mailer->send($email);
        } catch (\Throwable $e) {
            // On ne bloque pas la commande si mail fail
            $this->addFlash('warning', 'Commande validée, mais email non envoyé (config mail).');
        }

        // 10) Confirm page
        return $this->redirectToRoute('app_checkout_confirm', ['id' => $commande->getId()]);
    }

    #[Route('/checkout/confirm/{id}', name: 'app_checkout_confirm', methods: ['GET'])]
    public function confirm(Commande $commande): Response
    {
        return $this->render('front/checkout/confirm.html.twig', [
            'commande' => $commande,
        ]);
    }
}
