<?php

namespace App\Controller;

use App\Entity\Task;
use App\Repository\TaskRepository;
use App\Repository\CultureRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/tasks', name: 'api_tasks_')]
class TaskController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly TaskRepository $taskRepository,
        private readonly CultureRepository $cultureRepository,
    ) {
    }

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $tasks = $this->taskRepository->findAll();

        $data = array_map([$this, 'serializeTask'], $tasks);

        return $this->json($data);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $task = $this->taskRepository->find($id);

        if (!$task) {
            return $this->json(['message' => 'Task not found'], 404);
        }

        return $this->json($this->serializeTask($task));
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];

        $task = new Task();

        try {
            $this->mapDataToTask($task, $data, isNew: true);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['message' => $e->getMessage()], 400);
        }

        $this->em->persist($task);
        $this->em->flush();

        return $this->json($this->serializeTask($task), 201);
    }

    #[Route('/{id}', name: 'update', methods: ['PUT', 'PATCH'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $task = $this->taskRepository->find($id);

        if (!$task) {
            return $this->json(['message' => 'Task not found'], 404);
        }

        $data = json_decode($request->getContent(), true) ?? [];

        try {
            $this->mapDataToTask($task, $data, isNew: false);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['message' => $e->getMessage()], 400);
        }

        $this->em->flush();

        return $this->json($this->serializeTask($task));
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $task = $this->taskRepository->find($id);

        if (!$task) {
            return $this->json(['message' => 'Task not found'], 404);
        }

        $this->em->remove($task);
        $this->em->flush();

        return $this->json(null, 204);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function mapDataToTask(Task $task, array $data, bool $isNew): void
    {
        foreach (['titre', 'priorite', 'statut', 'type', 'dateDebut'] as $required) {
            if ($isNew && !\array_key_exists($required, $data)) {
                throw new \InvalidArgumentException(sprintf('Champ obligatoire manquant: %s', $required));
            }
        }

        if (isset($data['titre'])) {
            $task->setTitre($data['titre']);
        }
        if (isset($data['description'])) {
            $task->setDescription($data['description']);
        }
        if (isset($data['priorite'])) {
            $task->setPriorite($data['priorite']);
        }
        if (isset($data['statut'])) {
            $task->setStatut($data['statut']);
        }
        if (isset($data['type'])) {
            $task->setType($data['type']);
        }
        if (isset($data['localisation'])) {
            $task->setLocalisation($data['localisation']);
        }
        if (isset($data['parcelleId'])) {
            $task->setParcelleId($data['parcelleId']);
        }
        if (isset($data['cultureId'])) {
            $culture = $this->cultureRepository->find($data['cultureId']);
            if ($culture) {
                $task->setCulture($culture);
            }
        }
        if (isset($data['createdBy'])) {
            $task->setCreatedBy($data['createdBy']);
        }

        if (isset($data['dateDebut'])) {
            $task->setDateDebut($this->parseDate($data['dateDebut'], 'dateDebut'));
        }

        if (\array_key_exists('dateFin', $data)) {
            $task->setDateFin($data['dateFin'] !== null ? $this->parseDate($data['dateFin'], 'dateFin') : null);
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

    /**
     * @return array<string, int|string|null>
     */
    private function serializeTask(Task $task): array
    {
        $dateDebut = $task->getDateDebut();

        return [
            'idTask' => $task->getIdTask(),
            'titre' => $task->getTitre(),
            'description' => $task->getDescription(),
            'dateDebut' => $dateDebut?->format(\DateTimeInterface::ATOM),
            'dateFin' => $task->getDateFin()?->format(\DateTimeInterface::ATOM),
            'priorite' => $task->getPriorite(),
            'statut' => $task->getStatut(),
            'type' => $task->getType(),
            'localisation' => $task->getLocalisation(),
            'parcelleId' => $task->getParcelleId(),
            'cultureId' => $task->getCultureId(),
            'createdBy' => $task->getCreatedBy(),
        ];
    }
}

