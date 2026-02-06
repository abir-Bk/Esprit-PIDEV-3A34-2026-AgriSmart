<?php

namespace App\Controller;

use App\Entity\Parcelle;
use App\Form\ParcelleType;
use App\Repository\ParcelleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/parcelle')]
class ParcelleController extends AbstractController
{
    #[Route('/', name: 'app_parcelle_index', methods: ['GET', 'POST'])]
    public function index(ParcelleRepository $parcelleRepository, Request $request, EntityManagerInterface $entityManager): Response
    {
        $parcelle = new Parcelle();
        $form = $this->createForm(ParcelleType::class, $parcelle);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($parcelle);
            $entityManager->flush();
            return $this->redirectToRoute('app_parcelle_index');
        }

        return $this->render('parcelle/parcelle.html.twig', [
            'parcelles' => $parcelleRepository->findAll(),
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/edit', name: 'app_parcelle_edit', methods: ['POST'])]
    public function edit(Request $request, Parcelle $parcelle, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ParcelleType::class, $parcelle);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_parcelle_index');
    }

    #[Route('/delete/{id}', name: 'app_parcelle_delete', methods: ['POST'])]
    public function delete(Request $request, Parcelle $parcelle, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$parcelle->getId(), $request->request->get('_token'))) {
            $entityManager->remove($parcelle);
            $entityManager->flush();
        }
        return $this->redirectToRoute('app_parcelle_index');
    }
}