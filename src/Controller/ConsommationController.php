<?php

namespace App\Controller;

use App\Entity\Consommation;
use App\Form\ConsommationType;
use App\Repository\ConsommationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/consommation')]
final class ConsommationController extends AbstractController
{
    #[Route(name: 'app_consommation_index', methods: ['GET'])]
    public function index(ConsommationRepository $consommationRepository): Response
    {
        return $this->render('semi-public/consommation/index.html.twig', [
            'consommations' => $consommationRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_consommation_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $consommation = new Consommation();
        $form = $this->createForm(ConsommationType::class, $consommation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($consommation);
            $entityManager->flush();

            return $this->redirectToRoute('app_consommation_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('semi-public/consommation/new.html.twig', [
            'consommation' => $consommation,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_consommation_show', methods: ['GET'])]
    public function show(Consommation $consommation): Response
    {
        return $this->render('consommation/show.html.twig', [
            'consommation' => $consommation,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_consommation_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Consommation $consommation, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ConsommationType::class, $consommation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_consommation_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('semi-public/consommation/edit.html.twig', [
            'consommation' => $consommation,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_consommation_delete', methods: ['POST'])]
    public function delete(Request $request, Consommation $consommation, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$consommation->getId(), (string) $request->getPayload()->getString('_token'))) {
            $entityManager->remove($consommation);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_consommation_index', [], Response::HTTP_SEE_OTHER);
    }
}
