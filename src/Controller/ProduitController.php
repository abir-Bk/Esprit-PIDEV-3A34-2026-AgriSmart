<?php

namespace App\Controller;

use App\Entity\Produit;
use App\Form\ProduitType;
use App\Repository\ProduitRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/produit')]
final class ProduitController extends AbstractController
{
    private const DEV_EMAIL = 'dev@agri.tn';

    #[Route('', name: 'app_produit_index', methods: ['GET'])]
    public function index(
        Request $request,
        ProduitRepository $repo,
        PaginatorInterface $paginator
    ): Response {
        $q = trim((string) $request->query->get('q', ''));
        $type = trim((string) $request->query->get('type', ''));
        $categorie = trim((string) $request->query->get('categorie', ''));
        $promo = (string) $request->query->get('promo', '');
        $sort = (string) $request->query->get('sort', 'recent');

        $qb = $repo->createQueryBuilder('p');

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
        $perPage = 9;

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
        ]);
    }

    #[Route('/mes-offres', name: 'app_produit_mes_offres', methods: ['GET'])]
    public function mesOffres(ProduitRepository $repo, UserRepository $userRepo): Response
    {
        $user = $this->getCurrentUserOrDev($userRepo);
        if (!$user) {
            throw $this->createNotFoundException("User DEV introuvable (" . self::DEV_EMAIL . ").");
        }

        $mesProduits = $repo->findBy(['vendeur' => $user], ['createdAt' => 'DESC']);

        return $this->render('front/semi-public/produit/mes_offres.html.twig', [
            'produits' => $mesProduits,
        ]);
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
            throw $this->createNotFoundException("User DEV introuvable (" . self::DEV_EMAIL . ").");
        }
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
        }

        return $this->render('front/semi-public/produit/new.html.twig', [
            'produit' => $produit,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_produit_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Produit $produit): Response
    {
        return $this->render('front/semi-public/produit/show.html.twig', [
            'produit' => $produit,
        ]);
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

        if ($form->isSubmitted() && $form->isValid()) {
            if ($produit->getType() !== Produit::TYPE_LOCATION) {
                $produit->setLocationStart(null);
                $produit->setLocationEnd(null);
            }

            $this->handleImageUpload($form->get('imageFile')->getData(), $produit, $slugger);

            $em->flush();
            $this->addFlash('success', 'Votre annonce a été mise à jour avec succès.');
            return $this->redirectToRoute('app_produit_mes_offres');
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
            $em->remove($produit);
            $em->flush();
            $this->addFlash('success', 'Annonce retirée de la marketplace.');
        } else {
            $this->addFlash('danger', 'Token CSRF invalide.');
        }

        return $this->redirectToRoute('app_produit_mes_offres');
    }

    private function getCurrentUserOrDev(UserRepository $userRepo): ?object
    {
        $user = $this->getUser();
        if ($user) {
            return $user;
        }
        return $userRepo->findOneBy(['email' => self::DEV_EMAIL]);
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

        $file->move(
            $this->getParameter('kernel.project_dir') . '/public/uploads/produits',
            $newFilename
        );

        $produit->setImage('uploads/produits/' . $newFilename);
    }
}
