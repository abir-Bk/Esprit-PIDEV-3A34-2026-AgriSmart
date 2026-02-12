<?php

namespace App\Controller;

use App\Entity\Produit;
use App\Repository\CommandeRepository;
use App\Repository\ProduitRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AdminMarketplaceController extends AbstractController
{
    public function __construct(
        private ProduitRepository $produitRepository,
        private CommandeRepository $commandeRepository,
        private EntityManagerInterface $em,
    ) {
    }

    #[Route('/admin/marketplace', name: 'admin_marketplace_dashboard', methods: ['GET'])]
    public function dashboard(): Response
    {
        $produits = $this->produitRepository->findBy([], ['createdAt' => 'DESC'], 100);
        $commandes = $this->commandeRepository->findBy([], ['createdAt' => 'DESC'], 100);

        $totalProduits = $this->produitRepository->count([]);
        $totalCommandes = $this->commandeRepository->count([]);
        $bannedCount = $this->produitRepository->count(['banned' => true]);

        return $this->render('back/admin/admin_dashboard.html.twig', [
            'stats' => [
                'total_produits' => $totalProduits,
                'total_commandes' => $totalCommandes,
                'produits_bannis' => $bannedCount,
            ],
            'recent_produits' => array_slice($produits, 0, 5),
            'recent_commandes' => array_slice($commandes, 0, 5),
        ]);
    }

    #[Route('/admin/marketplace/commandes', name: 'admin_marketplace_commandes', methods: ['GET'])]
    public function commandes(): Response
    {
        $commandes = $this->commandeRepository->findBy([], ['createdAt' => 'DESC']);

        return $this->render('back/admin/marketplace_commandes.html.twig', [
            'commandes' => $commandes,
        ]);
    }

    #[Route('/admin/marketplace/produits', name: 'admin_marketplace_produits', methods: ['GET'])]
    public function produits(): Response
    {
        $produits = $this->produitRepository->findBy([], ['createdAt' => 'DESC']);

        return $this->render('back/admin/marketplace_produits.html.twig', [
            'produits' => $produits,
        ]);
    }

    #[Route('/admin/marketplace/produit/{id}/ban', name: 'admin_marketplace_produit_ban', methods: ['POST'])]
    public function toggleBan(int $id, Request $request): Response
    {
        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('produit_ban_' . $id, $token)) {
            $this->addFlash('error', 'Token invalide.');
            return $this->redirectToRoute('admin_marketplace_produits');
        }

        $produit = $this->produitRepository->find($id);
        if (!$produit) {
            throw $this->createNotFoundException('Produit introuvable.');
        }

        $produit->setBanned(!$produit->isBanned());
        $this->em->flush();

        $this->addFlash('success', $produit->isBanned()
            ? 'Le produit a été masqué du marketplace.'
            : 'Le produit est à nouveau visible sur le marketplace.');

        $referer = $request->headers->get('referer');
        if ($referer) {
            return $this->redirect($referer);
        }
        return $this->redirectToRoute('admin_marketplace_produits');
    }
}
