<?php

namespace App\Controller;

use App\Entity\Ressource;
use App\Form\RessourceType;
use App\Repository\RessourceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/ressource')]
class RessourceController extends AbstractController
{
    #[Route(name: 'app_ressource_index', methods: ['GET'])]
    public function index(RessourceRepository $repo): Response
    {
        return $this->render('front/ressource/ressource.html.twig', [
            'ressources' => $repo->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_ressource_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $ressource = new Ressource();
        $form = $this->createForm(RessourceType::class, $ressource);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Fix pour agriculteur_id qui ne peut pas être nul
            $ressource->setAgriculteurId(1); 

            $em->persist($ressource);
            $em->flush();
            $this->addFlash('success', 'Ressource ajoutée avec succès !');
            return $this->redirectToRoute('app_ressource_index');
        }

        return $this->render('front/ressource/new.html.twig', ['form' => $form]);
    }

    #[Route('/{id}/edit', name: 'app_ressource_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Ressource $ressource, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(RessourceType::class, $ressource);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('info', 'Stock mis à jour.');
            return $this->redirectToRoute('app_ressource_index');
        }

        return $this->render('front/ressource/edit.html.twig', [
            'form' => $form, 
            'ressource' => $ressource
        ]);
    }

    #[Route('/{id}/delete', name: 'app_ressource_delete', methods: ['POST'])]
    public function delete(Request $request, Ressource $ressource, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete'.$ressource->getId(), $request->request->get('_token'))) {
            $em->remove($ressource);
            $em->flush();
            $this->addFlash('warning', 'Ressource supprimée.');
        }
        return $this->redirectToRoute('app_ressource_index');
    }
}