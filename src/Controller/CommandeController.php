<?php

namespace App\Controller;

use App\Entity\Commande;
use App\Entity\Produit;
use App\Form\CommandeType;
use App\Repository\CommandeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/commande')]
final class CommandeController extends AbstractController
{
    #[Route('/mes-commandes', name: 'app_commande_mes_commandes', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function mesCommandes(CommandeRepository $repo): Response
    {
        $user = $this->getUser();

        $commandes = $repo->findBy(
            ['acheteur' => $user],
            ['id' => 'DESC']
        );

        return $this->render('front/commande/mes_commandes.html.twig', [
            'commandes' => $commandes,
        ]);
    }

    #[Route('/produit/{id}', name: 'app_commande_new', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_USER')]
    public function new(Produit $produit, Request $request, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();

        // ✅ Interdire acheter son propre produit
        if ($produit->getVendeur() && $produit->getVendeur() === $user) {
            $this->addFlash('danger', "Vous ne pouvez pas commander votre propre produit.");
            return $this->redirectToRoute('app_produit_show', ['id' => $produit->getId()]);
        }

        // ✅ Stock check (si vente)
        $stock = (int) ($produit->getQuantiteStock() ?? 0);
        if ($produit->getType() === Produit::TYPE_VENTE && $stock <= 0) {
            $this->addFlash('danger', "Produit en rupture de stock.");
            return $this->redirectToRoute('app_produit_show', ['id' => $produit->getId()]);
        }

        $commande = new Commande();
        $form = $this->createForm(CommandeType::class, $commande, [
            'max_stock' => max(1, $stock),
        ]);
        $form->handleRequest($request);

        // prix unitaire (promo si active)
        $prixUnitaire = (float) ($produit->getPrix() ?? 0);
        if ($produit->isPromotion() && $produit->getPromotionPrice() !== null) {
            $prixUnitaire = (float) $produit->getPromotionPrice();
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $quantite = (int) $form->get('quantite')->getData();
            if ($quantite < 1) {
                $quantite = 1;
            }

            // ✅ Re-check stock
            if ($produit->getType() === Produit::TYPE_VENTE && $quantite > $stock) {
                $this->addFlash('danger', "Quantité demandée > stock disponible.");
                return $this->redirectToRoute('app_commande_new', ['id' => $produit->getId()]);
            }

            $montantTotal = $prixUnitaire * $quantite;

            $commande->setAcheteur($user);
            $commande->setProduit($produit);
            $commande->setMontantTotal($montantTotal);
            $commande->setStatut('en_attente'); // ✅ en_attente par défaut (paiement plus tard)

            // ⚠️ Stock: à décrémenter UNIQUEMENT après paiement validé.
            // Exemple futur: si paiement OK => $produit->setQuantiteStock($stock - $quantite);

            $em->persist($commande);
            $em->flush();

            $this->addFlash('success', "Commande créée. Statut: en attente de paiement.");
            return $this->redirectToRoute('app_commande_mes_commandes');
        }

        return $this->render('front/commande/new.html.twig', [
            'produit' => $produit,
            'form' => $form,
            'prixUnitaire' => $prixUnitaire,
        ]);
    }
}
