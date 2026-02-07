<?php

namespace App\Controller;

use App\Entity\Produit;
use App\Form\ProduitType;
use App\Repository\ProduitRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/produit')]
final class ProduitController extends AbstractController
{
    #[Route(name: 'app_produit_index', methods: ['GET'])]
    public function index(Request $request, ProduitRepository $repo): Response
    {
        $q = trim((string) $request->query->get('q', ''));
        $type = trim((string) $request->query->get('type', ''));        // "vente" | "location"
        $categorie = trim((string) $request->query->get('categorie', ''));
        $promo = (string) $request->query->get('promo', '');            // "1" or ""

        $qb = $repo->createQueryBuilder('p');

        if ($q !== '') {
            $qb->andWhere('LOWER(p.nom) LIKE :q OR LOWER(p.description) LIKE :q')
               ->setParameter('q', '%' . mb_strtolower($q) . '%');
        }

        if (in_array($type, ['vente', 'location'], true)) {
            $qb->andWhere('p.type = :type')->setParameter('type', $type);
        }

        if ($categorie !== '') {
            $qb->andWhere('p.categorie = :cat')->setParameter('cat', $categorie);
        }

        if ($promo === '1') {
            $qb->andWhere('p.isPromotion = true');
        }

        // Tri
        $qb->orderBy('p.id', 'DESC');

        $produits = $qb->getQuery()->getResult();

        // Liste catégories (pour le dropdown)
        $catsRows = $repo->createQueryBuilder('p')
            ->select('DISTINCT p.categorie AS categorie')
            ->where('p.categorie IS NOT NULL')
            ->andWhere("p.categorie <> ''")
            ->orderBy('p.categorie', 'ASC')
            ->getQuery()
            ->getArrayResult();

        $categories = array_values(array_map(fn($r) => $r['categorie'], $catsRows));

        return $this->render('produit/index.html.twig', [
            'produits' => $produits,
            'filters' => [
                'q' => $q,
                'type' => $type,
                'categorie' => $categorie,
                'promo' => $promo,
            ],
            'categories' => $categories,
        ]);
    }

    #[Route('/new', name: 'app_produit_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $em,
        SluggerInterface $slugger,
    ): Response {
        $produit = new Produit();
        $form = $this->createForm(ProduitType::class, $produit, [
            'attr' => ['id' => 'produit-form'],
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            // ✅ Validations serveur (bloque vide / négatif)
            $prix = (float) ($produit->getPrix() ?? 0);
            $stock = (int) ($produit->getQuantiteStock() ?? 0);

            if (trim((string) $produit->getNom()) === '') {
                $form->addError(new FormError("Le nom est obligatoire."));
            }
            if (trim((string) $produit->getCategorie()) === '') {
                $form->addError(new FormError("La catégorie est obligatoire."));
            }
            if (!in_array((string) $produit->getType(), ['vente', 'location'], true)) {
                $form->addError(new FormError("Le type doit être 'vente' ou 'location'."));
            }
            if ($prix < 0) {
                $form->addError(new FormError("Le prix ne peut pas être négatif."));
            }
            if ($stock < 0) {
                $form->addError(new FormError("Le stock ne peut pas être négatif."));
            }

            // Promo (si ton entity a ces champs)
            if (method_exists($produit, 'isIsPromotion') && method_exists($produit, 'getPromoPrix')) {
                if ($produit->isIsPromotion()) {
                    $promoPrix = (float) ($produit->getPromoPrix() ?? 0);
                    if ($promoPrix < 0) {
                        $form->addError(new FormError("Le prix promo ne peut pas être négatif."));
                    }
                    if ($promoPrix > $prix) {
                        $form->addError(new FormError("Le prix promo doit être <= au prix normal."));
                    }
                }
            }

            // ✅ Upload image (si ProduitType contient imageFile)
            if ($form->isValid()) {
                if ($form->has('imageFile')) {
                    $file = $form->get('imageFile')->getData();
                    if ($file) {
                        $original = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                        $safe = $slugger->slug($original);
                        $newName = $safe . '-' . uniqid() . '.' . $file->guessExtension();

                        $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/produits';
                        if (!is_dir($uploadDir)) {
                            @mkdir($uploadDir, 0777, true);
                        }

                        $file->move($uploadDir, $newName);

                        // Stocke le chemin dans "image" (string)
                        if (method_exists($produit, 'setImage')) {
                            $produit->setImage('uploads/produits/' . $newName);
                        }
                    }
                }

                $em->persist($produit);
                $em->flush();

                return $this->redirectToRoute('app_produit_index', [], Response::HTTP_SEE_OTHER);
            }
        }

        return $this->render('produit/new.html.twig', [
            'produit' => $produit,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_produit_show', methods: ['GET'])]
    public function show(Produit $produit): Response
    {
        return $this->render('produit/show.html.twig', [
            'produit' => $produit,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_produit_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        Produit $produit,
        EntityManagerInterface $em,
        SluggerInterface $slugger,
    ): Response {
        $form = $this->createForm(ProduitType::class, $produit, [
            'attr' => ['id' => 'produit-form'],
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            // mêmes validations
            $prix = (float) ($produit->getPrix() ?? 0);
            $stock = (int) ($produit->getStock() ?? 0);

            if ($prix < 0) $form->addError(new FormError("Le prix ne peut pas être négatif."));
            if ($stock < 0) $form->addError(new FormError("Le stock ne peut pas être négatif."));

            if (method_exists($produit, 'isIsPromotion') && method_exists($produit, 'getPromoPrix')) {
                if ($produit->isIsPromotion()) {
                    $promoPrix = (float) ($produit->getPromoPrix() ?? 0);
                    if ($promoPrix < 0) $form->addError(new FormError("Le prix promo ne peut pas être négatif."));
                    if ($promoPrix > $prix) $form->addError(new FormError("Le prix promo doit être <= au prix normal."));
                }
            }

            if ($form->isValid()) {
                if ($form->has('imageFile')) {
                    $file = $form->get('imageFile')->getData();
                    if ($file) {
                        $original = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                        $safe = $slugger->slug($original);
                        $newName = $safe . '-' . uniqid() . '.' . $file->guessExtension();

                        $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/produits';
                        if (!is_dir($uploadDir)) @mkdir($uploadDir, 0777, true);

                        $file->move($uploadDir, $newName);

                        if (method_exists($produit, 'setImage')) {
                            $produit->setImage('uploads/produits/' . $newName);
                        }
                    }
                }

                $em->flush();
                return $this->redirectToRoute('app_produit_index', [], Response::HTTP_SEE_OTHER);
            }
        }

        return $this->render('produit/edit.html.twig', [
            'produit' => $produit,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_produit_delete', methods: ['POST'])]
    public function delete(Request $request, Produit $produit, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete'.$produit->getId(), $request->getPayload()->getString('_token'))) {
            $em->remove($produit);
            $em->flush();
        }

        return $this->redirectToRoute('app_produit_index', [], Response::HTTP_SEE_OTHER);
    }
}
