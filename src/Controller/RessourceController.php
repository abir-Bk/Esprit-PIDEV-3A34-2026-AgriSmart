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
class RessourceController extends AbstractController
{
    #[Route('/', name: 'app_ressource_index', methods: ['GET'])]
    public function index(RessourceRepository $repo): Response
    {
        // FILTRE : On ne récupère que les ressources de l'utilisateur connecté
        return $this->render('front/ressource/ressource.html.twig', [
            'ressources' => $repo->findBy(['user' => $this->getUser()]),
        ]);
    }

    #[Route('/new', name: 'app_ressource_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $ressource = new Ressource();
        $form = $this->createForm(RessourceType::class, $ressource);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Lier automatiquement la ressource à l'utilisateur actuel
            $ressource->setUser($this->getUser()); 

            $em->persist($ressource);
            $em->flush();
            
            $this->addFlash('success', 'Ressource ajoutée avec succès !');
            return $this->redirectToRoute('app_ressource_index');
        }

        return $this->render('front/ressource/new.html.twig', [
            'form' => $form->createView()
        ]);
    }

    #[Route('/{id}/edit', name: 'app_ressource_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Ressource $ressource, EntityManagerInterface $em): Response
    {
        // SÉCURITÉ : Vérifier si l'utilisateur est bien le propriétaire
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

        return $this->render('front/ressource/edit.html.twig', [
            'form' => $form->createView(), 
            'ressource' => $ressource
        ]);
    }

    #[Route('/{id}/delete', name: 'app_ressource_delete', methods: ['POST'])]
    public function delete(Request $request, Ressource $ressource, EntityManagerInterface $em): Response
    {
        // SÉCURITÉ : Vérifier le propriétaire ET le token CSRF
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