<?php

namespace App\Controller;

use App\Entity\Parcelle;
use App\Form\ParcelleType;
use App\Repository\ParcelleRepository;
use App\Repository\RessourceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/parcelle')]
class ParcelleController extends AbstractController
{
    #[Route('/', name: 'app_parcelle_index', methods: ['GET', 'POST'])]
    public function index(
        ParcelleRepository $parcelleRepository,
        RessourceRepository $ressourceRepository,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        // Recherche et tri
        $search = trim((string) $request->query->get('search', ''));
        $sort = (string) $request->query->get('sort', 'nbCultures');
        $direction = strtoupper((string) $request->query->get('direction', 'DESC'));
        $direction = $direction === 'ASC' ? 'ASC' : 'DESC';

        $qb = $parcelleRepository->createQueryBuilder('p')
            ->leftJoin('p.cultures', 'c')
            ->addSelect('p')
            ->addSelect('COUNT(c.id) AS HIDDEN nbCultures') // HIDDEN pour ne pas l'ajouter dans l'entité
            ->where('p.user = :user')
            ->setParameter('user', $user)
            ->groupBy('p.id');

        // Gestion du tri sécurisé
        switch ($sort) {
            case 'nom':
                $qb->orderBy('p.nom', $direction);
                break;
            case 'surface':
                $qb->orderBy('p.surface', $direction);
                break;
            case 'nbCultures':
            default:
                $sort = 'nbCultures';
                $qb->orderBy('nbCultures', $direction);
                break;
        }

        if ($search !== '') {
            $qb
                ->andWhere('LOWER(p.nom) LIKE LOWER(:search)')
                ->setParameter('search', '%' . $search . '%');
        }

        $parcelles = $qb->getQuery()->getResult();

        // Formulaire création nouvelle parcelle
        $parcelle = new Parcelle();
        $form = $this->createForm(ParcelleType::class, $parcelle);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $parcelle->setUser($user);
            $entityManager->persist($parcelle);
            $entityManager->flush();

            $this->addFlash('success', 'Parcelle créée avec succès !');
            return $this->redirectToRoute('app_parcelle_index', ['search' => $search]);
        }

        return $this->render('parcelle/parcelle.html.twig', [
            'parcelles'  => $parcelles,
            'ressources' => $ressourceRepository->findBy(['user' => $user]),
            'form'       => $form->createView(),
            'search'     => $search,           // pour pré-remplir le champ recherche
            'sort'       => $sort,
            'direction'  => $direction,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_parcelle_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        Parcelle $parcelle,
        EntityManagerInterface $entityManager
    ): Response {
        if ($parcelle->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(ParcelleType::class, $parcelle);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Parcelle modifiée.');
            return $this->redirectToRoute('app_parcelle_index');
        }

        return $this->render('parcelle/edit.html.twig', [
            'parcelle' => $parcelle,
            'form'     => $form->createView(),
        ]);
    }

    #[Route('/delete/{id}', name: 'app_parcelle_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        Parcelle $parcelle,
        EntityManagerInterface $entityManager
    ): Response {
        if ($parcelle->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        if ($this->isCsrfTokenValid('delete' . $parcelle->getId(), $request->request->get('_token'))) {
            $entityManager->remove($parcelle);
            $entityManager->flush();
            $this->addFlash('success', 'Parcelle supprimée.');
        }

        return $this->redirectToRoute('app_parcelle_index');
    }
}