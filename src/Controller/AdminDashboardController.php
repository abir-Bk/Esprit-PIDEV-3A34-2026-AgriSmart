<?php

namespace App\Controller;

use App\Repository\UserRepository;
use App\Repository\OffreRepository;
use App\Repository\ProduitRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
class AdminDashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'admin_dashboard')]
    public function dashboard(
        UserRepository $userRepository,
        OffreRepository $offreRepository,
        ProduitRepository $produitRepository,
    ): Response {

        // ── Users ────────────────────────────────────────────────────────────
        $totalUsers    = $userRepository->count([]);
        $activeUsers   = $userRepository->count(['status' => 'active']);
        $pendingUsers  = $userRepository->count(['status' => 'pending']);
        $disabledUsers = $userRepository->count(['status' => 'disabled']);

        // Users by role
        $roles = ['admin', 'employee', 'agriculteur', 'fournisseur'];
        $usersByRole = [];
        foreach ($roles as $role) {
            $usersByRole[$role] = $userRepository->count(['role' => $role]);
        }

        // New users last 30 days
        $since = new \DateTime('-30 days');
        $newUsersThisMonth = $userRepository->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->where('u.createdAt >= :since')
            ->setParameter('since', $since)
            ->getQuery()
            ->getSingleScalarResult();

        // ── Offres ───────────────────────────────────────────────────────────
        $totalOffres    = $offreRepository->count([]);
        $offresApproved = $offreRepository->count(['statutValidation' => 'approuvée']);
        $offresPending  = $offreRepository->count(['statutValidation' => 'en_attente']);
        $offresRefused  = $offreRepository->count(['statutValidation' => 'refusée']);
        $offresActive   = $offreRepository->count(['isActive' => true]);

        // ── Produits ─────────────────────────────────────────────────────────
        $totalProduits  = $produitRepository->count([]);
        $produitsVente  = $produitRepository->count(['type' => 'vente']);
        $produitsLocation = $produitRepository->count(['type' => 'location']);
        $produitsPromo  = $produitRepository->count(['isPromotion' => true]);
        $produitsBanned = $produitRepository->count(['banned' => true]);

        // Produits by category (for chart)
        $catRows = $produitRepository->createQueryBuilder('p')
            ->select('p.categorie AS cat, COUNT(p.id) AS cnt')
            ->where('p.categorie IS NOT NULL')
            ->andWhere("p.categorie <> ''")
            ->groupBy('p.categorie')
            ->orderBy('cnt', 'DESC')
            ->setMaxResults(6)
            ->getQuery()
            ->getArrayResult();

        $produitCategories = array_column($catRows, 'cat');
        $produitCatCounts  = array_map('intval', array_column($catRows, 'cnt'));

        // ── Recent users (last 5) ─────────────────────────────────────────
        $recentUsers = $userRepository->findBy([], ['createdAt' => 'DESC'], 5);

        // ── Recent offres (last 5 pending) ───────────────────────────────
        $recentOffres = $offreRepository->findBy(
            ['statutValidation' => 'en_attente'],
            ['id' => 'DESC'],
            5
        );

        return $this->render('back/admin/dashboard.html.twig', [
            // Users
            'totalUsers'        => $totalUsers,
            'activeUsers'       => $activeUsers,
            'pendingUsers'      => $pendingUsers,
            'disabledUsers'     => $disabledUsers,
            'usersByRole'       => $usersByRole,
            'newUsersThisMonth' => $newUsersThisMonth,
            'recentUsers'       => $recentUsers,

            // Offres
            'totalOffres'    => $totalOffres,
            'offresApproved' => $offresApproved,
            'offresPending'  => $offresPending,
            'offresRefused'  => $offresRefused,
            'offresActive'   => $offresActive,
            'recentOffres'   => $recentOffres,

            // Produits
            'totalProduits'     => $totalProduits,
            'produitsVente'     => $produitsVente,
            'produitsLocation'  => $produitsLocation,
            'produitsPromo'     => $produitsPromo,
            'produitsBanned'    => $produitsBanned,
            'produitCategories' => json_encode($produitCategories),
            'produitCatCounts'  => json_encode($produitCatCounts),
        ]);
    }
}
