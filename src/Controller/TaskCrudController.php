<?php

namespace App\Controller;

use App\Entity\Task;
use App\Form\TaskType;
use App\Repository\TaskRepository;
use App\Repository\TaskAssignmentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/tasks', name: 'tasks_')]
class TaskCrudController extends AbstractController
{
    #[Route('/dashboard', name: 'dashboard', methods: ['GET'])]
    public function dashboard(TaskRepository $taskRepository, TaskAssignmentRepository $taskAssignmentRepository): Response
    {
        $today = new \DateTimeImmutable('today');

        $totalTasks = $taskRepository->count([]);
        $todoTasks = $taskRepository->count(['statut' => 'todo']);
        $inProgressTasks = $taskRepository->count(['statut' => 'en_cours']);
        $doneTasks = $taskRepository->count(['statut' => 'termine']);

        $totalAssignments = $taskAssignmentRepository->count([]);
        $todayTasks = $taskRepository->findTodayTasks($today);

        return $this->render('front/task/dashboard.html.twig', [
            'totalTasks' => $totalTasks,
            'todoTasks' => $todoTasks,
            'inProgressTasks' => $inProgressTasks,
            'doneTasks' => $doneTasks,
            'totalAssignments' => $totalAssignments,
            'todayTasks' => $todayTasks,
            'today' => $today,
        ]);
    }

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(Request $request, TaskRepository $taskRepository): Response
    {
        $search = trim((string) $request->query->get('q', ''));
        $sort = (string) $request->query->get('sort', 'dateDebut');
        $direction = strtoupper((string) $request->query->get('direction', 'DESC'));

        $allowedSort = [
            'id' => 't.idTask',
            'titre' => 't.titre',
            'type' => 't.type',
            'priorite' => 't.priorite',
            'statut' => 't.statut',
            'dateDebut' => 't.dateDebut',
            'dateFin' => 't.dateFin',
        ];

        $sortField = $allowedSort[$sort] ?? $allowedSort['dateDebut'];
        $sortDirection = $direction === 'ASC' ? 'ASC' : 'DESC';

        $qb = $taskRepository->createQueryBuilder('t');

        if ($search !== '') {
            $qb
                ->andWhere('t.titre LIKE :q OR t.description LIKE :q OR t.type LIKE :q OR t.priorite LIKE :q OR t.statut LIKE :q')
                ->setParameter('q', '%' . $search . '%');
        }

        $tasks = $qb
            ->orderBy($sortField, $sortDirection)
            ->getQuery()
            ->getResult();

        return $this->render('front/task/index.html.twig', [
            'tasks' => $tasks,
            'q' => $search,
            'sort' => $sort,
            'direction' => $sortDirection,
        ]);
    }

    #[Route('/calendar', name: 'calendar', methods: ['GET'])]
    public function calendar(TaskRepository $taskRepository): Response
    {
        $tasks = $taskRepository->findAll();
        $events = [];
        foreach ($tasks as $task) {
            $events[] = [
                'id' => $task->getIdTask(),
                'title' => $task->getTitre() . ' (' . $task->getType() . ')',
                'start' => $task->getDateDebut()->format('c'),
                'end' => $task->getDateFin() ? $task->getDateFin()->format('c') : $task->getDateDebut()->format('c'),
                'url' => $this->generateUrl('tasks_show', ['id' => $task->getIdTask()]),
                'extendedProps' => [
                    'type' => $task->getType(),
                    'statut' => $task->getStatut(),
                    'priorite' => $task->getPriorite(),
                ],
            ];
        }

        return $this->render('front/task/calendar.html.twig', [
            'tasks' => $tasks,
            'events' => $events,
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $task = new Task();
        $form = $this->createForm(TaskType::class, $task);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $selectedUser = $form->get('createdByUser')->getData();
            if ($selectedUser !== null) {
                $task->setCreatedBy($selectedUser->getId());
            }

            $em->persist($task);
            $em->flush();

            $this->addFlash('success', 'Tâche créée avec succès.');

            return $this->redirectToRoute('tasks_index');
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
            $selectedUser = $form->get('createdByUser')->getData();
            if ($selectedUser !== null) {
                $task->setCreatedBy($selectedUser->getId());
            }

            $em->flush();

            $this->addFlash('success', 'Tâche mise à jour avec succès.');

            return $this->redirectToRoute('tasks_index');
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

        return $this->redirectToRoute('tasks_index');
    }
}

