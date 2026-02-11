<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use App\Form\OffreType;
use App\Entity\Offre;
use App\Repository\OffreRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\EntityManagerInterface;  

final class OffreController extends AbstractController
{
    #[Route('/admin/offre/new', name: 'app_offre_new', methods: ['GET', 'POST'])]
    public function add(Request $request, EntityManagerInterface $em): Response
    {
        $offre = new Offre();
        $form = $this->createForm(OffreType::class, $offre);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($offre);
            $em->flush();

            return $this->redirectToRoute('app_admin_offres');
        }

        return $this->render('back/offre/add.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    // Cette route sert à ta liste admin spécifique
    #[Route('/admin/liste-offres', name: 'app_offre_admin_index')]
    public function index(Request $request, OffreRepository $repo): Response
    {
        $searchTerm = $request->query->get('search');
        
        if ($searchTerm) {
            $offres = $repo->createQueryBuilder('o')
                ->where('o.title LIKE :term OR o.lieu LIKE :term')
                ->setParameter('term', '%'.$searchTerm.'%')
                ->getQuery()
                ->getResult();
        } else {
            $offres = $repo->findAll();
        }

        return $this->render('back/admin/admin_list.html.twig', [
            'offres' => $offres
        ]);
    }

    // CORRECTION : J'ai mis le nom 'app_admin_offres' ici pour corriger ton erreur
    #[Route('/admin/offres', name: 'app_admin_offres')]
    public function adminIndex(OffreRepository $repo): Response
    {
        $offres = $repo->findAll();

        // ATTENTION : Ici j'ai pointé vers le template back (index) et pas show (front)
        // car show.html.twig attend une seule offre, pas une liste.
        return $this->render('back/offre/index.html.twig', [
            'offres' => $offres,
        ]);
    }

    #[Route('/offre/{id}', name: 'app_offre_show', requirements: ['id' => '\d+'])]
    public function show(int $id, EntityManagerInterface $entityManager): Response
    {
        $offre = $entityManager->getRepository(Offre::class)->find($id);

        if (!$offre) {
            throw $this->createNotFoundException("L'offre demandée n'existe pas.");
        }

        return $this->render('front/offre/show.html.twig', [
            'offre' => $offre,
        ]);
    }

    #[Route('/admin/offre/edit/{id}', name: 'app_offre_edit')]
    public function form(Offre $offre = null, Request $request, EntityManagerInterface $em): Response
    {
        if (!$offre) { $offre = new Offre(); }
        
        $form = $this->createForm(OffreType::class, $offre);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($offre);
            $em->flush();
            $this->addFlash('success', 'Offre enregistrée !');
            return $this->redirectToRoute('app_admin_offres');
        }

        return $this->render('back/offre/form.html.twig', [
            'form' => $form->createView(),
            'editMode' => $offre->getId() !== null
        ]);
    }

    #[Route('/admin/offre/delete/{id}', name: 'app_offre_delete', methods: ['POST'])]
    public function delete(Offre $offre, Request $request, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete'.$offre->getId(), $request->request->get('_token'))) {
            $em->remove($offre);
            $em->flush();
        }
        return $this->redirectToRoute('app_admin_offres');
    }

    #[Route('/admin/offre/{id}/details', name: 'app_admin_offre_details')]
    public function details(Offre $offre): Response
    {
        return $this->render('back/offre/details.html.twig', [
            'offre' => $offre,
            'demandes' => $offre->getDemandes(), // AJOUTE CETTE LIGNE
        ]);
    }

    #[Route('/offres-emploi', name: 'app_offre_index_front')] 
    public function indexFront(Request $request, OffreRepository $repo): Response
    {
        $searchTerm = $request->query->get('search');
        
        if ($searchTerm) {
            $offres = $repo->createQueryBuilder('o')
                ->where('o.title LIKE :term OR o.lieu LIKE :term')
                ->setParameter('term', '%'.$searchTerm.'%')
                ->getQuery()
                ->getResult();
        } else {
            $offres = $repo->findBy(['isActive' => true]);    
        }

        return $this->render('front/offre/index.html.twig', [
            'offres' => $offres
        ]);
    }

    #[Route('/admin/offre/toggle/{id}', name: 'app_offre_toggle')]
    public function toggle(int $id, OffreRepository $repo, EntityManagerInterface $em): Response
    {
        $offre = $repo->find($id);

        if (!$offre) {
            throw $this->createNotFoundException('Offre non trouvée');
        }

        $offre->setIsActive(!$offre->isActive());
        $em->flush();

        $this->addFlash('success', 'Le statut de l\'offre a été mis à jour !');
        return $this->redirectToRoute('app_admin_offres');
    }
}