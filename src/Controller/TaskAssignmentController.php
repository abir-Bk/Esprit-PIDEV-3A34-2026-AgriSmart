<?php

namespace App\Controller;

use App\Entity\Task;
use App\Entity\TaskAssignment;
use App\Repository\TaskAssignmentRepository;
use App\Repository\TaskRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/task-assignments', name: 'api_task_assignments_')]
class TaskAssignmentController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly TaskAssignmentRepository $assignmentRepository,
        private readonly TaskRepository $taskRepository,
    ) {
    }

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $assignments = $this->assignmentRepository->findAll();

        $data = array_map([$this, 'serializeAssignment'], $assignments);

        return $this->json($data);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $assignment = $this->assignmentRepository->find($id);

        if (!$assignment) {
            return $this->json(['message' => 'Assignment not found'], 404);
        }

        return $this->json($this->serializeAssignment($assignment));
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];

        $assignment = new TaskAssignment();

        try {
            $this->mapDataToAssignment($assignment, $data, isNew: true);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['message' => $e->getMessage()], 400);
        }

        $this->em->persist($assignment);
        $this->em->flush();

        return $this->json($this->serializeAssignment($assignment), 201);
    }

    #[Route('/{id}', name: 'update', methods: ['PUT', 'PATCH'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $assignment = $this->assignmentRepository->find($id);

        if (!$assignment) {
            return $this->json(['message' => 'Assignment not found'], 404);
        }

        $data = json_decode($request->getContent(), true) ?? [];

        try {
            $this->mapDataToAssignment($assignment, $data, isNew: false);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['message' => $e->getMessage()], 400);
        }

        $this->em->flush();

        return $this->json($this->serializeAssignment($assignment));
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $assignment = $this->assignmentRepository->find($id);

        if (!$assignment) {
            return $this->json(['message' => 'Assignment not found'], 404);
        }

        $this->em->remove($assignment);
        $this->em->flush();

        return $this->json(null, 204);
    }

    private function mapDataToAssignment(TaskAssignment $assignment, array $data, bool $isNew): void
    {
        foreach (['taskId', 'workerId', 'dateAssignment', 'statut'] as $required) {
            if ($isNew && !\array_key_exists($required, $data)) {
                throw new \InvalidArgumentException(sprintf('Champ obligatoire manquant: %s', $required));
            }
        }

        if (isset($data['taskId'])) {
            $task = $this->taskRepository->find($data['taskId']);
            if (!$task instanceof Task) {
                throw new \InvalidArgumentException('Task introuvable pour taskId=' . $data['taskId']);
            }
            $assignment->setTask($task);
        }

        if (isset($data['workerId'])) {
            $assignment->setWorkerId((int) $data['workerId']);
        }

        if (isset($data['dateAssignment'])) {
            $assignment->setDateAssignment($this->parseDate($data['dateAssignment'], 'dateAssignment'));
        }

        if (isset($data['statut'])) {
            $assignment->setStatut($data['statut']);
        }
    }

    private function parseDate(string $value, string $fieldName): \DateTimeInterface
    {
        try {
            return new \DateTime($value);
        } catch (\Exception) {
            throw new \InvalidArgumentException(sprintf('Format de date invalide pour %s', $fieldName));
        }
    }

    private function serializeAssignment(TaskAssignment $assignment): array
    {
        return [
            'idAssignment' => $assignment->getIdAssignment(),
            'taskId' => $assignment->getTask()?->getIdTask(),
            'workerId' => $assignment->getWorkerId(),
            'dateAssignment' => $assignment->getDateAssignment()->format(\DateTimeInterface::ATOM),
            'statut' => $assignment->getStatut(),
        ];
    }
}

