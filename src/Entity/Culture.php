<?php

namespace App\Entity;

use App\Repository\CultureRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CultureRepository::class)]
class Culture
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $typeCulture = null;

    #[ORM\Column(length: 255)]
    private ?string $variete = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTime $datePlantation = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTime $dateRecoltePrevue = null;

    #[ORM\Column(length: 255)]
    private ?string $statut = null;

    #[ORM\ManyToOne(inversedBy: 'cultures')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Parcelle $parcelle = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTypeCulture(): ?string
    {
        return $this->typeCulture;
    }

    public function setTypeCulture(string $typeCulture): static
    {
        $this->typeCulture = $typeCulture;

        return $this;
    }

    public function getVariete(): ?string
    {
        return $this->variete;
    }

    public function setVariete(string $variete): static
    {
        $this->variete = $variete;

        return $this;
    }

    public function getDatePlantation(): ?\DateTime
    {
        return $this->datePlantation;
    }

    public function setDatePlantation(\DateTime $datePlantation): static
    {
        $this->datePlantation = $datePlantation;

        return $this;
    }

    public function getDateRecoltePrevue(): ?\DateTime
    {
        return $this->dateRecoltePrevue;
    }

    public function setDateRecoltePrevue(\DateTime $dateRecoltePrevue): static
    {
        $this->dateRecoltePrevue = $dateRecoltePrevue;

        return $this;
    }

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): static
    {
        $this->statut = $statut;

        return $this;
    }

    public function getParcelle(): ?Parcelle
    {
        return $this->parcelle;
    }

    public function setParcelle(?Parcelle $parcelle): static
    {
        $this->parcelle = $parcelle;

        return $this;
    }
}
