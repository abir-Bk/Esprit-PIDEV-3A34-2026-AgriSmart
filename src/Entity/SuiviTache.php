<?php

namespace App\Entity;

use App\Repository\SuiviTacheRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: SuiviTacheRepository::class)]
#[ORM\Table(name: 'suivi_tache')]
class SuiviTache
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_suivi')]
    private ?int $idSuivi = null;

    #[ORM\Column(type: 'datetime')]
    #[Assert\NotBlank(message: 'La date est obligatoire.')]
    private ?\DateTimeInterface $date = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le rendement est obligatoire.')]
    #[Assert\Length(
        min: 2,
        max: 255,
        minMessage: 'Le rendement doit contenir au moins {{ limit }} caractères.',
        maxMessage: 'Le rendement ne peut pas dépasser {{ limit }} caractères.'
    )]
    private ?string $rendement = null;

    #[ORM\Column(type: 'text')]
    #[Assert\NotBlank(message: 'La description des problèmes est obligatoire.')]
    #[Assert\Length(
        min: 10,
        minMessage: 'Veuillez décrire les problèmes plus en détail (au moins {{ limit }} caractères).'
    )]
    private ?string $problemes = null;

    #[ORM\Column(type: 'text')]
    #[Assert\NotBlank(message: 'La solution est obligatoire.')]
    #[Assert\Length(
        min: 10,
        minMessage: 'Veuillez décrire la solution plus en détail (au moins {{ limit }} caractères).'
    )]
    private ?string $solution = null;

    #[ORM\ManyToOne(targetEntity: Task::class)]
    #[ORM\JoinColumn(name: 'id_tache', referencedColumnName: 'id_task', nullable: false)]
    private ?Task $task = null;

    public function getIdSuivi(): ?int
    {
        return $this->idSuivi;
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(?\DateTimeInterface $date): self
    {
        $this->date = $date;
        return $this;
    }

    public function getRendement(): ?string
    {
        return $this->rendement;
    }

    public function setRendement(?string $rendement): self
    {
        $this->rendement = $rendement;
        return $this;
    }

    public function getProblemes(): ?string
    {
        return $this->problemes;
    }

    public function setProblemes(?string $problemes): self
    {
        $this->problemes = $problemes;
        return $this;
    }

    public function getSolution(): ?string
    {
        return $this->solution;
    }

    public function setSolution(?string $solution): self
    {
        $this->solution = $solution;
        return $this;
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
}
