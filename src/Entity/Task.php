<?php

namespace App\Entity;

use App\Repository\TaskRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TaskRepository::class)]
#[ORM\Table(name: 'task')]
class Task
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_task')]
    private ?int $idTask = null;

    #[ORM\Column(length: 255)]
    private ?string $titre = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $resume = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $dateDebut = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $dateFin = null;

    /**
     * low / medium / high
     */
    #[ORM\Column(length: 20)]
    private ?string $priorite = null;

    /**
     * todo / en_cours / a_valider / termine
     */
    #[ORM\Column(length: 20)]
    private ?string $statut = null;

    /**
     * arrosage, récolte, fertilisation, etc.
     */
    #[ORM\Column(length: 50)]
    private ?string $type = null;

    /**
     * Localisation textuelle de la tâche (adresse, coordonnées, etc.)
     */
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $localisation = null;

    /**
     * Référence éventuelle à une parcelle (id technique)
     */
  #[ORM\ManyToOne(targetEntity: Parcelle::class)]
#[ORM\JoinColumn(name: 'parcelle_id', nullable: true)]
private ?Parcelle $parcelle = null;

    /**
     * Référence éventuelle à une culture
     */
    #[ORM\ManyToOne(targetEntity: Culture::class)]
    #[ORM\JoinColumn(name: 'culture_id', nullable: true)]
    private ?Culture $culture = null;

    /**
     * Identifiant de l'utilisateur qui a créé la tâche.
     * On le stocke comme entier pour ne pas dépendre encore du module Utilisateur.
     */
    #[ORM\Column(name: 'created_by', nullable: true)]
    private ?int $createdBy = null;

    /** @var Collection<int, TaskAssignment> */
    #[ORM\OneToMany(mappedBy: 'task', targetEntity: TaskAssignment::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $assignments;

    public function __construct()
    {
        $this->assignments = new ArrayCollection();
    }

    public function getIdTask(): ?int
    {
        return $this->idTask;
    }

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(?string $titre): self
    {
        $this->titre = $titre;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getResume(): ?string
    {
        return $this->resume;
    }

    public function setResume(?string $resume): self
    {
        $this->resume = $resume;

        return $this;
    }

    public function getDateDebut(): ?\DateTimeInterface
    {
        return $this->dateDebut;
    }

    public function setDateDebut(?\DateTimeInterface $dateDebut): self
    {
        $this->dateDebut = $dateDebut;

        return $this;
    }

    public function getDateFin(): ?\DateTimeInterface
    {
        return $this->dateFin;
    }

    public function setDateFin(?\DateTimeInterface $dateFin): self
    {
        $this->dateFin = $dateFin;

        return $this;
    }

    public function getPriorite(): ?string
    {
        return $this->priorite;
    }

    public function setPriorite(?string $priorite): self
    {
        $this->priorite = $priorite;

        return $this;
    }

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(?string $statut): self
    {
        $this->statut = $statut;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getLocalisation(): ?string
    {
        return $this->localisation;
    }

    public function setLocalisation(?string $localisation): self
    {
        $this->localisation = $localisation;

        return $this;
    }

   public function getParcelleId(): ?int
{
    return $this->parcelle?->getId();
}
    public function setParcelle(?Parcelle $parcelle): self
{
    $this->parcelle = $parcelle;
    return $this;
}

    public function getCulture(): ?Culture
    {
        return $this->culture;
    }

    public function setCulture(?Culture $culture): self
    {
        $this->culture = $culture;

        return $this;
    }

    public function getCultureId(): ?int
    {
        return $this->culture?->getId();
    }

    public function setCultureId(?int $cultureId): self
    {
        // Deprecated: use setCulture() instead
        return $this;
    }

    public function getCreatedBy(): ?int
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?int $createdBy): self
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    /**
     * @return Collection<int, TaskAssignment>
     */
    public function getAssignments(): Collection
    {
        return $this->assignments;
    }

    public function addAssignment(TaskAssignment $assignment): self
    {
        if (!$this->assignments->contains($assignment)) {
            $this->assignments->add($assignment);
            $assignment->setTask($this);
        }

        return $this;
    }

    public function removeAssignment(TaskAssignment $assignment): self
    {
        if ($this->assignments->removeElement($assignment)) {
            if ($assignment->getTask() === $this) {
                $assignment->setTask(null);
            }
        }

        return $this;
    }
}
