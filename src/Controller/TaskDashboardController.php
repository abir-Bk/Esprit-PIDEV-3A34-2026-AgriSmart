<?php

namespace App\Controller;

use App\Repository\TaskRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/tasks', name: 'admin_tasks_')]
class TaskDashboardController extends AbstractController
{
    public function __construct(
        private readonly TaskRepository $taskRepository,
    ) {
    }

    #[Route('/dashboard', name: 'dashboard')]
    public function dashboard(): Response
    {
        $totalTasks = $this->taskRepository->count([]);
        $completedTasks = $this->taskRepository->count(['statut' => 'termine']);
        $pendingTasks = $this->taskRepository->count(['statut' => 'todo']);
        $inProgressTasks = $this->taskRepository->count(['statut' => 'en_cours']);

        // For chart data, group by status
        $statusCounts = [
            'pending' => $pendingTasks,
            'in_progress' => $inProgressTasks,
            'completed' => $completedTasks,
        ];

        return $this->render('back/admin/task_dashboard.html.twig', [
            'totalTasks' => $totalTasks,
            'completedTasks' => $completedTasks,
            'pendingTasks' => $pendingTasks,
            'inProgressTasks' => $inProgressTasks,
            'statusCounts' => $statusCounts,
        ]);
    }
}