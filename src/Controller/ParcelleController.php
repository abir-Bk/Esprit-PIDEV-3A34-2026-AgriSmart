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
use Symfony\Component\Security\Http\Attribute\IsGranted; // Optionnel mais recommandé

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
        // 1. On récupère l'utilisateur connecté
        $user = $this->getUser();

        // Sécurité : Si pas connecté, redirection vers login
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $parcelle = new Parcelle();
        $form = $this->createForm(ParcelleType::class, $parcelle);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // 2. ON ASSOCIE L'UTILISATEUR AUTOMATIQUEMENT ICI
            // Assure-tu que ton entité Parcelle a une méthode setUser()
            $parcelle->setUser($user); 

            $entityManager->persist($parcelle);
            $entityManager->flush();
            
            $this->addFlash('success', 'Parcelle créée et associée à votre compte !');
            return $this->redirectToRoute('app_parcelle_index');
        }

        return $this->render('parcelle/parcelle.html.twig', [
            // 3. ON FILTRE POUR NE VOIR QUE MES PARCELLES
            'parcelles' => $parcelleRepository->findBy(['user' => $user]),
            'ressources' => $ressourceRepository->findAll(),
            'form' => $form->createView(),
        ]);
    }

    // Pense aussi à sécuriser l'edit et le delete
    #[Route('/delete/{id}', name: 'app_parcelle_delete', methods: ['POST'])]
    public function delete(Request $request, Parcelle $parcelle, EntityManagerInterface $entityManager): Response
    {
        // Sécurité : Vérifier que la parcelle appartient bien à l'utilisateur connecté
        if ($parcelle->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException("Vous n'avez pas le droit de supprimer cette parcelle.");
        }

        if ($this->isCsrfTokenValid('delete'.$parcelle->getId(), $request->request->get('_token'))) {
            $entityManager->remove($parcelle);
            $entityManager->flush();
            $this->addFlash('success', 'Parcelle supprimée.');
        }
        return $this->redirectToRoute('app_parcelle_index');
    }
}