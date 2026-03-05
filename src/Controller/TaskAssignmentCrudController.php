<?php

namespace App\Controller;

use App\Entity\TaskAssignment;
use App\Form\TaskAssignmentType;
use App\Repository\TaskAssignmentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/task-assignments', name: 'task_assignments_')]
#[IsGranted('ROLE_AGRICULTEUR')]
class TaskAssignmentCrudController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(TaskAssignmentRepository $assignmentRepository): Response
    {
        return $this->render('front/task_assignment/index.html.twig', [
            'assignments' => $assignmentRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $assignment = new TaskAssignment();
        $form = $this->createForm(TaskAssignmentType::class, $assignment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($assignment);
            $em->flush();

            $this->addFlash('success', 'Affectation créée avec succès.');

            return $this->redirectToRoute('task_assignments_index');
        }

        return $this->render('front/task_assignment/new.html.twig', [
            'assignment' => $assignment,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(int $id, TaskAssignmentRepository $assignmentRepository): Response
    {
        $assignment = $assignmentRepository->find($id);
        if (!$assignment) {
            throw $this->createNotFoundException('Affectation introuvable.');
        }

        return $this->render('front/task_assignment/show.html.twig', [
            'assignment' => $assignment,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(int $id, Request $request, TaskAssignmentRepository $assignmentRepository, EntityManagerInterface $em): Response
    {
        $assignment = $assignmentRepository->find($id);
        if (!$assignment) {
            throw $this->createNotFoundException('Affectation introuvable.');
        }

        $form = $this->createForm(TaskAssignmentType::class, $assignment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            $this->addFlash('success', 'Affectation mise à jour avec succès.');

            return $this->redirectToRoute('task_assignments_index');
        }

        return $this->render('front/task_assignment/edit.html.twig', [
            'assignment' => $assignment,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'delete', methods: ['POST'])]
    public function delete(int $id, Request $request, TaskAssignmentRepository $assignmentRepository, EntityManagerInterface $em): Response
    {
        $assignment = $assignmentRepository->find($id);
        if (!$assignment) {
            throw $this->createNotFoundException('Affectation introuvable.');
        }

        if ($this->isCsrfTokenValid('delete' . $assignment->getIdAssignment(), (string) $request->request->get('_token'))) {
            $em->remove($assignment);
            $em->flush();
            $this->addFlash('success', 'Affectation supprimée avec succès.');
        }

        return $this->redirectToRoute('task_assignments_index');
    }
}
