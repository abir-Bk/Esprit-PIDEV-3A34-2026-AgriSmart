<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Task;
use App\Repository\TaskAssignmentRepository;
use Doctrine\ORM\EntityManagerInterface;
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

        $assignments = $assignmentRepository->findForWorker($user);

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

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(int $id, TaskAssignmentRepository $assignmentRepository): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException('Vous devez être connecté en tant qu\'employé.');
        }

        $assignment = $assignmentRepository->findOneBy([
            'idAssignment' => $id,
            'worker' => $user
        ]);

        if (!$assignment) {
            throw $this->createNotFoundException('Affectation introuvable.');
        }

        return $this->render('front/employee/task_show.html.twig', [
            'assignment' => $assignment,
            'task' => $assignment->getTask(),
        ]);
    }

    #[Route('/{id}/start', name: 'start', methods: ['POST'])]
    public function start(int $id, TaskAssignmentRepository $assignmentRepository, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException('Vous devez être connecté en tant qu\'employé.');
        }

        $assignment = $assignmentRepository->findOneBy([
            'idAssignment' => $id,
            'worker' => $user
        ]);

        if (!$assignment) {
            throw $this->createNotFoundException('Affectation introuvable.');
        }

        $task = $assignment->getTask();
        if ($task && $task->getStatut() === 'todo') {
            $task->setStatut('en_cours');
            $assignment->setStatut('acceptee');
            $em->flush();
            $this->addFlash('success', 'Tâche commencée !');
        }

        return $this->redirectToRoute('employee_tasks_show', ['id' => $id]);
    }
}
