<?php

namespace App\Controller;

use App\Entity\Produit;
use App\Entity\LocationReservation;
use App\Form\ProduitType;
use App\Repository\LocationReservationRepository;
use App\Repository\MarketplaceMessageRepository;
use App\Repository\ProduitRepository;
use App\Repository\UserRepository;
use App\Repository\WishlistItemRepository;
use App\Service\HuggingFaceService;
use App\Service\MarketplaceRecommendationService;
use App\Service\PanierService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\DBAL\LockMode;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/produit')]
final class ProduitController extends AbstractController
{
    public function __construct(
        private readonly PanierService $panierService,
        private readonly MarketplaceRecommendationService $recommendationService,
        private readonly ProduitRepository $produitRepository,
        private readonly LocationReservationRepository $locationReservationRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('', name: 'app_produit_index', methods: ['GET'])]
    public function index(
        Request $request,
        ProduitRepository $repo,
        PaginatorInterface $paginator,
        MarketplaceMessageRepository $messageRepository,
        WishlistItemRepository $wishlistRepository,
    ): Response {
        $q = trim((string) $request->query->get('q', ''));
        $type = trim((string) $request->query->get('type', ''));
        $categorie = trim((string) $request->query->get('categorie', ''));
        $promo = (string) $request->query->get('promo', '');
        $sort = (string) $request->query->get('sort', 'recent');

        $qb = $repo->createQueryBuilder('p')
            ->andWhere('p.banned = false');

        if ($q !== '') {
            $qb->andWhere('LOWER(p.nom) LIKE :q OR LOWER(p.description) LIKE :q')
                ->setParameter('q', '%' . mb_strtolower($q) . '%');
        }

        if (in_array($type, [Produit::TYPE_VENTE, Produit::TYPE_LOCATION], true)) {
            $qb->andWhere('p.type = :type')->setParameter('type', $type);
        }

        if ($categorie !== '') {
            $qb->andWhere('p.categorie = :cat')->setParameter('cat', $categorie);
        }

        if ($promo === '1') {
            $qb->andWhere('p.isPromotion = true');
        }

        switch ($sort) {
            case 'price_asc':
                $qb->orderBy('p.prix', 'ASC');
                break;
            case 'price_desc':
                $qb->orderBy('p.prix', 'DESC');
                break;
            default:
                $qb->orderBy('p.createdAt', 'DESC');
                break;
        }

        // ✅ PAGINATION (paramètres de tri personnalisés pour ne pas conflit avec notre "sort" = recent/price_asc/price_desc)
        $page = max(1, (int) $request->query->get('page', 1));
        $perPage = 6;

        // Évite que KNP utilise le paramètre "sort" (recent/price_asc/price_desc) comme nom de champ
        $paginatorOptions = [
            'sortFieldParameterName' => 'tri',
            'sortDirectionParameterName' => 'ordre',
        ];

        // IMPORTANT: on passe le QueryBuilder au paginator (pas getResult())
        $produits = $paginator->paginate($qb, $page, $perPage, $paginatorOptions);

        // catégories dynamiques
        $catsRows = $repo->createQueryBuilder('p2')
            ->select('DISTINCT p2.categorie AS categorie')
            ->where('p2.categorie IS NOT NULL')
            ->andWhere("p2.categorie <> ''")
            ->orderBy('p2.categorie', 'ASC')
            ->getQuery()
            ->getArrayResult();

        $categoriesList = array_values(array_map(static fn($r) => $r['categorie'], $catsRows));

        $currentUser = $this->getUser();
        $messagerieUnreadCount = $currentUser instanceof \App\Entity\User
            ? $messageRepository->countUnreadForUser($currentUser)
            : 0;

        $wishlistProductIds = [];
        $wishlistCount = 0;
        if ($currentUser instanceof \App\Entity\User) {
            $wishlistCount = $wishlistRepository->count(['user' => $currentUser]);
            $productIds = [];
            foreach ($produits as $produit) {
                if ($produit instanceof Produit && $produit->getId() !== null) {
                    $productIds[] = $produit->getId();
                }
            }

            $wishlistProductIds = $wishlistRepository->getWishlistedProductIds($currentUser, $productIds);
        }

        return $this->render('front/semi-public/produit/index.html.twig', [
            'produits' => $produits, // ✅ maintenant c'est un objet pagination KNP
            'filters' => [
                'q' => $q,
                'type' => $type,
                'categorie' => $categorie,
                'promo' => $promo,
                'sort' => $sort,
                'page' => $page,
            ],
            'categories' => $categoriesList,
            'messagerieUnreadCount' => $messagerieUnreadCount,
            'wishlistProductIds' => $wishlistProductIds,
            'wishlistCount' => $wishlistCount,
        ]);
    }

    #[Route('/mes-offres', name: 'app_produit_mes_offres', methods: ['GET'])]
    public function mesOffres(ProduitRepository $repo, UserRepository $userRepo): Response
    {
        $user = $this->getCurrentUserOrDev($userRepo);
        if (!$user) {
            throw $this->createAccessDeniedException('Connexion requise.');
        }

        $mesProduits = $repo->findBy(['vendeur' => $user], ['createdAt' => 'DESC']);

        return $this->render('front/semi-public/produit/mes_offres.html.twig', [
            'produits' => $mesProduits,
        ]);
    }

    #[Route('/mes-reservations', name: 'app_produit_mes_reservations', methods: ['GET'])]
    public function mesReservations(UserRepository $userRepo): Response
    {
        $user = $this->getCurrentUserOrDev($userRepo);
        if (!$user instanceof \App\Entity\User) {
            throw $this->createAccessDeniedException('Connexion requise.');
        }

        $reservations = $this->locationReservationRepository->findForVendeur($user);

        return $this->render('front/semi-public/produit/mes_reservations.html.twig', [
            'reservations' => $reservations,
        ]);
    }

    #[Route('/suggest-description', name: 'app_produit_suggest_description', methods: ['POST'])]
    public function suggestDescription(Request $request, HuggingFaceService $huggingFace): JsonResponse
    {
        $nom = trim((string) $request->request->get('nom', ''));
        $categorie = trim((string) $request->request->get('categorie', ''));

        if ($nom === '' || $categorie === '') {
            return $this->json(['error' => 'Nom et catégorie requis.'], 400);
        }

        try {
            $suggestion = $huggingFace->suggestDescription($nom, $categorie);
            return $this->json(['description' => $suggestion]);
        } catch (\Throwable $e) {
            return $this->json(['error' => 'Erreur lors de la génération : ' . $e->getMessage()], 500);
        }
    }

    #[Route('/new', name: 'app_front_produit_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $em,
        SluggerInterface $slugger,
        UserRepository $userRepo
    ): Response {
        $produit = new Produit();

        $vendeur = $this->getCurrentUserOrDev($userRepo);
        if (!$vendeur) {
            return $this->redirectToRoute('app_login');
        }
        // $vendeur is App\Entity\User thanks to method signature
        $produit->setVendeur($vendeur);

        $form = $this->createForm(ProduitType::class, $produit);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($produit->getPrix() !== null && $produit->getPrix() < 0) {
                $form->get('prix')->addError(new FormError('Le prix ne peut pas être négatif.'));
            }

            if ($form->isValid()) {
                if ($produit->getType() !== Produit::TYPE_LOCATION) {
                    $produit->setLocationStart(null);
                    $produit->setLocationEnd(null);
                }

                $this->handleImageUpload($form->get('imageFile')->getData(), $produit, $slugger);

                $em->persist($produit);
                $em->flush();

                $this->addFlash('success', 'Félicitations ! Votre annonce est maintenant en ligne.');
                return $this->redirectToRoute('app_produit_mes_offres');
            }

            $this->addFlash('danger', 'Impossible de publier l\'annonce. Merci de corriger les champs en erreur.');
        }

        return $this->render('front/semi-public/produit/new.html.twig', [
            'produit' => $produit,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_produit_show', methods: ['GET'], requirements: ['id' => '\\d+'])]
    public function show(Produit $produit, RequestStack $requestStack, MarketplaceMessageRepository $messageRepository): Response
    {
        $reservationRanges = $this->locationReservationRepository->findReservedRangesForProduit($produit);

        $currentUser = $this->getUser();
        $messagerieUnreadCount = $currentUser instanceof \App\Entity\User
            ? $messageRepository->countUnreadForUser($currentUser)
            : 0;

        $recommendedProducts = [];
        if ($currentUser instanceof \App\Entity\User) {
            $recommendedProducts = $this->recommendationService->recommendForUser($currentUser, [
                'q' => (string) $produit->getNom(),
                'type' => (string) ($produit->getType() ?? ''),
                'categorie' => (string) ($produit->getCategorie() ?? ''),
                'promo' => '',
                'sort' => 'recent',
                'page' => 1,
            ], 6);

            $recommendedProducts = array_values(array_filter(
                $recommendedProducts,
                static fn(Produit $p): bool => $p->getId() !== $produit->getId()
            ));
        }

        if ($recommendedProducts === []) {
            $fallback = $this->produitRepository->createQueryBuilder('p')
                ->andWhere('p.banned = false')
                ->andWhere('p.id != :currentId')
                ->setParameter('currentId', $produit->getId())
                ->setMaxResults(6)
                ->orderBy('p.createdAt', 'DESC');

            if ($produit->getCategorie()) {
                $fallback->andWhere('p.categorie = :categorie')
                    ->setParameter('categorie', $produit->getCategorie());
            }

            if ($produit->getType()) {
                $fallback->andWhere('p.type = :type')
                    ->setParameter('type', $produit->getType());
            }

            /** @var Produit[] $recommendedProducts */
            $recommendedProducts = $fallback->getQuery()->getResult();
        }

        return $this->render('front/semi-public/produit/show.html.twig', [
            'produit' => $produit,
            'reservationRanges' => $reservationRanges,
            'messagerieUnreadCount' => $messagerieUnreadCount,
            'recommendedProducts' => array_slice($recommendedProducts, 0, 3),
        ]);
    }

    #[Route('/{id}/reserve', name: 'app_produit_reserve', methods: ['POST'], requirements: ['id' => '\\d+'])]
    public function reserve(Produit $produit, Request $request, RequestStack $requestStack, UserRepository $userRepo): Response
    {
        if ($produit->getType() !== Produit::TYPE_LOCATION) {
            $this->addFlash('warning', 'La réservation est disponible uniquement pour les produits en location.');
            return $this->redirectToRoute('app_produit_show', ['id' => $produit->getId()]);
        }

        if (!$this->isCsrfTokenValid('produit_reserve_' . $produit->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('danger', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_produit_show', ['id' => $produit->getId()]);
        }

        $user = $this->getCurrentUserOrDev($userRepo);
        if ($user && $produit->getVendeur() && $produit->getVendeur() === $user) {
            $this->addFlash('warning', 'Vous ne pouvez pas réserver votre propre annonce.');
            return $this->redirectToRoute('app_produit_show', ['id' => $produit->getId()]);
        }

        $startRaw = trim((string) $request->request->get('start_date', ''));
        $endRaw = trim((string) $request->request->get('end_date', ''));

        $start = \DateTimeImmutable::createFromFormat('Y-m-d', $startRaw) ?: null;
        $end = \DateTimeImmutable::createFromFormat('Y-m-d', $endRaw) ?: null;
        $today = new \DateTimeImmutable('today');

        if (!$start || !$end) {
            $this->addFlash('danger', 'Veuillez sélectionner une date de début et une date de fin valides.');
            return $this->redirectToRoute('app_produit_show', ['id' => $produit->getId()]);
        }

        if ($start < $today || $end < $today || $end < $start) {
            $this->addFlash('danger', 'La plage sélectionnée est invalide.');
            return $this->redirectToRoute('app_produit_show', ['id' => $produit->getId()]);
        }

        $availableStart = $produit->getLocationStart();
        $availableEnd = $produit->getLocationEnd();

        if ($availableStart && $start < $availableStart) {
            $this->addFlash('warning', 'La date de début est avant la disponibilité du produit.');
            return $this->redirectToRoute('app_produit_show', ['id' => $produit->getId()]);
        }
        if ($availableEnd && $end > $availableEnd) {
            $this->addFlash('warning', 'La date de fin dépasse la disponibilité du produit.');
            return $this->redirectToRoute('app_produit_show', ['id' => $produit->getId()]);
        }

        if (!$user instanceof \App\Entity\User) {
            $this->addFlash('danger', 'Connexion requise pour réserver.');
            return $this->redirectToRoute('app_login');
        }

        try {
            $this->entityManager->beginTransaction();
            $this->entityManager->lock($produit, LockMode::PESSIMISTIC_WRITE);

            if ($this->locationReservationRepository->hasOverlap($produit, $start, $end)) {
                $this->entityManager->rollback();
                $this->addFlash('danger', 'Ce créneau est déjà réservé. Merci de choisir d’autres dates.');
                return $this->redirectToRoute('app_produit_show', ['id' => $produit->getId()]);
            }

            $days = ((int) $start->diff($end)->days) + 1;
            $unitPrice = $produit->getPrixEffectif() ?? 0;
            $total = $days * $unitPrice;

            $reservation = (new LocationReservation())
                ->setProduit($produit)
                ->setLocataire($user)
                ->setStartDate($start)
                ->setEndDate($end)
                ->setDays($days)
                ->setUnitPrice((float) $unitPrice)
                ->setTotalPrice((float) $total)
                ->setStatus(LocationReservation::STATUS_ACTIVE);

            $this->entityManager->persist($reservation);
            $this->entityManager->flush();
            $this->entityManager->commit();
        } catch (\Throwable $exception) {
            if ($this->entityManager->getConnection()->isTransactionActive()) {
                $this->entityManager->rollback();
            }

            $this->addFlash('danger', 'Une erreur est survenue pendant la réservation. Réessayez.');
            return $this->redirectToRoute('app_produit_show', ['id' => $produit->getId()]);
        }

        $days = ((int) $start->diff($end)->days) + 1;
        $unitPrice = $produit->getPrixEffectif() ?? 0;
        $total = $days * $unitPrice;

        $this->addFlash(
            'success',
            sprintf(
                'Réservation ajoutée au panier du %s au %s (%d jour%s) — Total estimé: %.2f TND. Choisissez maintenant votre mode de paiement.',
                $start->format('d/m/Y'),
                $end->format('d/m/Y'),
                $days,
                $days > 1 ? 's' : '',
                $total
            )
        );

        $produitId = $produit->getId();
        
        if ($produitId === null) {
            throw new \LogicException('Produit ID should not be null here');
        }

        $this->panierService->setLocationBooking((int) $produitId, $start->format('Y-m-d'), $end->format('Y-m-d'), $days);
        $this->panierService->setQty((int) $produitId, $days);

        return $this->redirectToRoute('app_checkout_index');
    }

    #[Route('/{id}/edit', name: 'app_produit_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        Produit $produit,
        EntityManagerInterface $em,
        SluggerInterface $slugger,
        UserRepository $userRepo
    ): Response {
        $user = $this->getCurrentUserOrDev($userRepo);

        if ($this->getUser() !== null) {
            if ($produit->getVendeur() !== $user && !$this->isGranted('ROLE_ADMIN')) {
                throw $this->createAccessDeniedException("Vous n'avez pas le droit de modifier cette annonce.");
            }
        }

        $form = $this->createForm(ProduitType::class, $produit);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                if ($produit->getType() !== Produit::TYPE_LOCATION) {
                    $produit->setLocationStart(null);
                    $produit->setLocationEnd(null);
                }

                $this->handleImageUpload($form->get('imageFile')->getData(), $produit, $slugger);

                $em->flush();
                $this->addFlash('success', 'Votre annonce a été mise à jour avec succès.');
                return $this->redirectToRoute('app_produit_mes_offres');
            }

            $this->addFlash('danger', 'Mise à jour impossible. Merci de corriger les champs en erreur.');
        }

        return $this->render('front/semi-public/produit/edit.html.twig', [
            'produit' => $produit,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_produit_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        Produit $produit,
        EntityManagerInterface $em,
        UserRepository $userRepo
    ): Response {
        $user = $this->getCurrentUserOrDev($userRepo);

        if ($this->getUser() !== null) {
            if ($produit->getVendeur() !== $user && !$this->isGranted('ROLE_ADMIN')) {
                throw $this->createAccessDeniedException("Vous n'avez pas le droit de supprimer cette annonce.");
            }
        }

        if ($this->isCsrfTokenValid('delete' . $produit->getId(), (string) $request->request->get('_token'))) {
            try {
                $em->remove($produit);
                $em->flush();
                $this->addFlash('success', 'Annonce supprimée avec succès.');
            } catch (\Throwable) {
                $this->addFlash('danger', 'Suppression impossible pour le moment. Veuillez réessayer.');
            }
        } else {
            $this->addFlash('danger', 'Token CSRF invalide.');
        }

        return $this->redirectToRoute('app_produit_mes_offres');
    }

    private function getCurrentUserOrDev(UserRepository $userRepo): ?\App\Entity\User
    {
        $user = $this->getUser();
        if ($user instanceof \App\Entity\User) {
            return $user;
        }
        return null;
    }

    private function handleImageUpload(?UploadedFile $file, Produit $produit, SluggerInterface $slugger): void
    {
        if (!$file)
            return;

        $allowedMime = ['image/jpeg', 'image/png', 'image/webp'];
        if (!in_array((string) $file->getMimeType(), $allowedMime, true)) {
            $this->addFlash('danger', 'Format image invalide (jpg, png, webp).');
            return;
        }

        if ($file->getSize() !== null && $file->getSize() > 3 * 1024 * 1024) {
            $this->addFlash('danger', 'Image trop grande (max 3MB).');
            return;
        }

        $originalFilename = pathinfo((string) $file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $slugger->slug($originalFilename);
        $newFilename = $safeFilename . '-' . bin2hex(random_bytes(6)) . '.' . ($file->guessExtension() ?: 'jpg');

        $projDir = $this->getParameter('kernel.project_dir');
        if (!is_string($projDir)) {
            throw new \RuntimeException('kernel.project_dir must be a string');
        }
        $file->move(
            $projDir . '/public/uploads/produits',
            $newFilename
        );

        $produit->setImage('uploads/produits/' . $newFilename);
    }
}
