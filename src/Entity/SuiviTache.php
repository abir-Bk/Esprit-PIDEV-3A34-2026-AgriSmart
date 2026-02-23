<?php

namespace App\Entity;

use App\Repository\SuiviTacheRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Validator\Constraints as Assert;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

#[ORM\Entity(repositoryClass: SuiviTacheRepository::class)]
#[ORM\Table(name: 'suivi_tache')]
#[Vich\Uploadable]
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

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $image = null;

    #[Vich\UploadableField(mapping: 'suivi_images', fileNameProperty: 'image')]
    #[Assert\File(
        maxSize: '5M',
        mimeTypes: ['image/jpeg', 'image/png'],
        mimeTypesMessage: 'Veuillez uploader une image JPEG ou PNG valide (max 5 Mo)'
    )]
    private ?File $imageFile = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

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

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): self
    {
        $this->image = $image;
        return $this;
    }

    public function getImageFile(): ?File
    {
        return $this->imageFile;
    }

    public function setImageFile(?File $imageFile = null): void
    {
        $this->imageFile = $imageFile;

        if (null !== $imageFile) {
            $this->updatedAt = new \DateTime();
        }
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }
}
