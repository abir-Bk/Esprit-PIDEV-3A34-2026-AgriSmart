<?php

namespace App\Controller;

use App\Entity\Produit;
use App\Entity\User;
use App\Entity\WishlistItem;
use App\Repository\MarketplaceMessageRepository;
use App\Repository\WishlistItemRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('IS_AUTHENTICATED_FULLY')]
#[Route('/wishlist')]
final class WishlistController extends AbstractController
{
    #[Route('', name: 'app_wishlist_index', methods: ['GET'])]
    public function index(WishlistItemRepository $wishlistRepository, MarketplaceMessageRepository $messageRepository): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException('Connexion requise.');
        }

        $items = $wishlistRepository->findBy(['user' => $user], ['createdAt' => 'DESC']);
        $messagerieUnreadCount = $messageRepository->countUnreadForUser($user);
        $wishlistCount = $wishlistRepository->count(['user' => $user]);

        return $this->render('front/semi-public/wishlist/index.html.twig', [
            'items' => $items,
            'messagerieUnreadCount' => $messagerieUnreadCount,
            'wishlistCount' => $wishlistCount,
        ]);
    }

    #[Route('/toggle/{id}', name: 'app_wishlist_toggle', methods: ['POST'], requirements: ['id' => '\\d+'])]
    public function toggle(
        Produit $produit,
        Request $request,
        WishlistItemRepository $wishlistRepository,
        EntityManagerInterface $entityManager,
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException('Connexion requise.');
        }

        if (!$this->isCsrfTokenValid('wishlist_toggle_' . $produit->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('danger', 'Action invalide.');

            return $this->redirectToRoute('app_produit_index');
        }

        $existing = $wishlistRepository->findOneByUserAndProduit($user, $produit);
        if ($existing) {
            $entityManager->remove($existing);
            $entityManager->flush();

            $this->addFlash('info', 'Produit retiré de votre wishlist.');
            return $this->redirect($request->headers->get('referer') ?: $this->generateUrl('app_produit_index'));
        }

        $item = (new WishlistItem())
            ->setUser($user)
            ->setProduit($produit);

        $entityManager->persist($item);
        $entityManager->flush();

        $this->addFlash('success', 'Produit ajouté à votre wishlist.');

        return $this->redirect($request->headers->get('referer') ?: $this->generateUrl('app_produit_index'));
    }

    #[Route('/toggle-json/{id}', name: 'app_wishlist_toggle_json', methods: ['POST'], requirements: ['id' => '\\d+'])]
    public function toggleJson(
        Produit $produit,
        Request $request,
        WishlistItemRepository $wishlistRepository,
        EntityManagerInterface $entityManager,
    ): JsonResponse {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['ok' => false, 'error' => 'Connexion requise.'], 401);
        }

        if (!$this->isCsrfTokenValid('wishlist_toggle_' . $produit->getId(), (string) $request->request->get('_token'))) {
            return new JsonResponse(['ok' => false, 'error' => 'Token invalide.'], 400);
        }

        $existing = $wishlistRepository->findOneByUserAndProduit($user, $produit);
        if ($existing) {
            $entityManager->remove($existing);
            $entityManager->flush();

            return new JsonResponse(['ok' => true, 'wishlisted' => false]);
        }

        $item = (new WishlistItem())
            ->setUser($user)
            ->setProduit($produit);

        $entityManager->persist($item);
        $entityManager->flush();

        return new JsonResponse(['ok' => true, 'wishlisted' => true]);
    }
}
