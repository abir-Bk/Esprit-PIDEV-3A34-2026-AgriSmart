<?php

namespace App\Entity;

use App\Repository\TaskAssignmentRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TaskAssignmentRepository::class)]
#[ORM\Table(name: 'task_assignment')]
class TaskAssignment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_assignment')]
    private ?int $idAssignment = null;

    #[ORM\ManyToOne(targetEntity: Task::class, inversedBy: 'assignments')]
    #[ORM\JoinColumn(name: 'task_id', referencedColumnName: 'id_task', nullable: false, onDelete: 'CASCADE')]
    private ?Task $task = null;

    /**
     * Identifiant de l'ouvrier (Utilisateur) affecté.
     * Stocké comme entier pour rester découplé du module Utilisateur.
     */
    #[ORM\Column(name: 'worker_id')]
    private int $workerId;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $dateAssignment;

    /**
     * assignée / acceptée / réalisée
     */
    #[ORM\Column(length: 20)]
    private string $statut;

    public function getIdAssignment(): ?int
    {
        return $this->idAssignment;
    }

    public function getTask(): ?Task
    {
        return $this->task;
    }

    public function setTask(?Task $task): self
    {
        $this->task = $task;

        return $this;
    }

    public function getWorkerId(): int
    {
        return $this->workerId;
    }

    public function setWorkerId(int $workerId): self
    {
        $this->workerId = $workerId;

        return $this;
    }

    public function getDateAssignment(): \DateTimeInterface
    {
        return $this->dateAssignment;
    }

    public function setDateAssignment(\DateTimeInterface $dateAssignment): self
    {
        $this->dateAssignment = $dateAssignment;

        return $this;
    }

    public function getStatut(): string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): self
    {
        $this->statut = $statut;

        return $this;
    }
}

