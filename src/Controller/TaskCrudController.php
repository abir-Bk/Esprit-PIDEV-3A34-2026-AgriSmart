<?php

namespace App\Controller;

use App\Entity\Task;
use App\Form\TaskType;
use App\Repository\TaskRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/tasks', name: 'admin_tasks_')]
class TaskCrudController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(TaskRepository $taskRepository): Response
    {
        return $this->render('front/task/index.html.twig', [
            'tasks' => $taskRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $task = new Task();
        $form = $this->createForm(TaskType::class, $task);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($task);
            $em->flush();

            $this->addFlash('success', 'Tâche créée avec succès.');

            return $this->redirectToRoute('admin_tasks_index');
        }

        return $this->render('front/task/new.html.twig', [
            'task' => $task,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(int $id, TaskRepository $taskRepository): Response
    {
        $task = $taskRepository->find($id);
        if (!$task) {
            throw $this->createNotFoundException('Tâche introuvable.');
        }

        return $this->render('front/task/show.html.twig', [
            'task' => $task,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(int $id, Request $request, TaskRepository $taskRepository, EntityManagerInterface $em): Response
    {
        $task = $taskRepository->find($id);
        if (!$task) {
            throw $this->createNotFoundException('Tâche introuvable.');
        }

        $form = $this->createForm(TaskType::class, $task);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            $this->addFlash('success', 'Tâche mise à jour avec succès.');

            return $this->redirectToRoute('admin_tasks_index');
        }

        return $this->render('front/task/edit.html.twig', [
            'task' => $task,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'delete', methods: ['POST'])]
    public function delete(int $id, Request $request, TaskRepository $taskRepository, EntityManagerInterface $em): Response
    {
        $task = $taskRepository->find($id);
        if (!$task) {
            throw $this->createNotFoundException('Tâche introuvable.');
        }

        if ($this->isCsrfTokenValid('delete'.$task->getIdTask(), $request->request->get('_token'))) {
            $em->remove($task);
            $em->flush();
            $this->addFlash('success', 'Tâche supprimée avec succès.');
        }

        return $this->redirectToRoute('admin_tasks_index');
    }
}

