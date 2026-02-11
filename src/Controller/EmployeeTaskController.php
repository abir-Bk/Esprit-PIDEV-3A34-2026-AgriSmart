<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\TaskAssignmentRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/employee/tasks', name: 'employee_tasks_')]
class EmployeeTaskController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(TaskAssignmentRepository $assignmentRepository): Response
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            throw $this->createAccessDeniedException('Vous devez être connecté en tant qu\'employé.');
        }

        $workerId = $user->getId();
        $assignments = $assignmentRepository->findForWorker($workerId);

        $total = \count($assignments);
        $assignes = \array_filter($assignments, static fn($a) => $a->getStatut() === 'assignee');
        $acceptees = \array_filter($assignments, static fn($a) => $a->getStatut() === 'acceptee');
        $realisees = \array_filter($assignments, static fn($a) => $a->getStatut() === 'realisee');

        return $this->render('front/employee/tasks.html.twig', [
            'assignments' => $assignments,
            'totalAssignments' => $total,
            'assignesCount' => \count($assignes),
            'accepteesCount' => \count($acceptees),
            'realiseesCount' => \count($realisees),
            'employee' => $user,
        ]);
    }
}

