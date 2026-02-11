<?php

namespace App\Controller;

use App\Entity\Ressource;
use App\Form\RessourceType;
use App\Repository\RessourceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/ressource')]
#[IsGranted('ROLE_AGRICULTEUR')] // force login with role
class RessourceController extends AbstractController
{
    #[Route('/', name: 'app_ressource_index', methods: ['GET'])]
    public function index(Request $request, RessourceRepository $repo): Response
    {
        $user = $this->getUser();

        $typeSearch = trim((string) $request->query->get('type', ''));
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = 8;

        $qb = $repo->createQueryBuilder('r')
            ->where('r.user = :user')
            ->setParameter('user', $user);

        if ($typeSearch !== '') {
            $qb->andWhere('LOWER(r.type) LIKE LOWER(:type)')
               ->setParameter('type', '%' . $typeSearch . '%');
        }

        $qbCount = clone $qb;
        $qbCount->resetDQLPart('orderBy');
        $qbCount->select('COUNT(r.id)');
        $totalRessources = (int) $qbCount->getQuery()->getSingleScalarResult();

        $totalPages = $totalRessources > 0 ? (int) ceil($totalRessources / $limit) : 1;
        if ($page > $totalPages) $page = $totalPages;

        $qb->orderBy('r.nom', 'ASC')
           ->setFirstResult(($page - 1) * $limit)
           ->setMaxResults($limit);

        $ressources = $qb->getQuery()->getResult();

        $qbTypes = $repo->createQueryBuilder('rt')
            ->select('DISTINCT rt.type AS type')
            ->where('rt.user = :user')
            ->setParameter('user', $user)
            ->orderBy('rt.type', 'ASC');

        $typesRows = $qbTypes->getQuery()->getScalarResult();
        $typesDisponibles = array_column($typesRows, 'type');

        return $this->render('front/semi-public/ressource/ressource.html.twig', [
            'ressources'      => $ressources,
            'typeSearch'      => $typeSearch,
            'currentPage'     => $page,
            'totalPages'      => $totalPages,
            'totalResults'    => $totalRessources,
            'typesDisponibles'=> $typesDisponibles,
        ]);
    }

    #[Route('/new', name: 'app_ressource_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $ressource = new Ressource();
        $ressource->setUser($this->getUser()); // link to current user

        $form = $this->createForm(RessourceType::class, $ressource);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($ressource);
            $em->flush();

            $this->addFlash('success', 'Ressource ajoutée avec succès !');
            return $this->redirectToRoute('app_ressource_index');
        }

        return $this->render('front/semi-public/ressource/new.html.twig', [
            'form' => $form->createView()
        ]);
    }

    #[Route('/{id}/edit', name: 'app_ressource_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Ressource $ressource, EntityManagerInterface $em): Response
    {
        if ($ressource->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Vous n\'avez pas le droit de modifier cette ressource.');
        }

        $form = $this->createForm(RessourceType::class, $ressource);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('info', 'Stock mis à jour avec succès.');
            return $this->redirectToRoute('app_ressource_index');
        }

        return $this->render('front/semi-public/ressource/edit.html.twig', [
            'form' => $form->createView(),
            'ressource' => $ressource
        ]);
    }

    #[Route('/{id}/delete', name: 'app_ressource_delete', methods: ['POST'])]
    public function delete(Request $request, Ressource $ressource, EntityManagerInterface $em): Response
    {
        if ($ressource->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Action interdite.');
        }

        if ($this->isCsrfTokenValid('delete'.$ressource->getId(), $request->request->get('_token'))) {
            $em->remove($ressource);
            $em->flush();
            $this->addFlash('danger', 'Ressource supprimée.');
        }

        return $this->redirectToRoute('app_ressource_index');
    }
}