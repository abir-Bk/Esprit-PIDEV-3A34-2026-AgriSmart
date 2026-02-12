<?php

namespace App\Entity;

use App\Repository\CultureRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CultureRepository::class)]
class Culture
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Le type de culture est obligatoire.")]
    #[Assert\Length(
        min: 2,
        max: 100,
        minMessage: "Le type de culture doit contenir au moins {{ limit }} caractères.",
        maxMessage: "Le type de culture ne peut pas dépasser {{ limit }} caractères."
    )]
    private ?string $typeCulture = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "La variété est obligatoire.")]
    #[Assert\Length(
        min: 2,
        max: 100,
        minMessage: "La variété doit contenir au moins {{ limit }} caractères.",
        maxMessage: "La variété ne peut pas dépasser {{ limit }} caractères."
    )]
    private ?string $variete = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Assert\NotBlank(message: "La date de plantation est obligatoire.")]
    #[Assert\Type(\DateTimeInterface::class, message: "Date de plantation invalide.")]
    #[Assert\LessThanOrEqual(value: "today", message: "La date de plantation ne peut pas être dans le futur.")]
    private ?\DateTime $datePlantation = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Assert\NotBlank(message: "La date de récolte prévue est obligatoire.")]
    #[Assert\Type(\DateTimeInterface::class, message: "Date de récolte invalide.")]
    #[Assert\GreaterThan(propertyPath: "datePlantation", message: "La date de récolte doit être postérieure à la date de plantation.")]
    private ?\DateTime $dateRecoltePrevue = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Le statut est obligatoire.")]
    #[Assert\Choice(
        choices: ["En croissance", "Besoin d'eau", "Mature", "Récolté", "Maladie", "Traitement"],
        message: "Statut invalide."
    )]
    private ?string $statut = null;

    #[ORM\ManyToOne(inversedBy: 'cultures')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: "La parcelle est obligatoire.")]
    private ?Parcelle $parcelle = null;

    #[ORM\OneToMany(targetEntity: Consommation::class, mappedBy: 'culture')]
    private Collection $consommations;

    public function __construct()
    {
        $this->consommations = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTypeCulture(): ?string
    {
        return $this->typeCulture;
    }

    public function setTypeCulture(?string $typeCulture): static
    {
        $this->typeCulture = $typeCulture;
        return $this;
    }

    public function getVariete(): ?string
    {
        return $this->variete;
    }

    public function setVariete(?string $variete): static
    {
        $this->variete = $variete;
        return $this;
    }

    public function getDatePlantation(): ?\DateTime
    {
        return $this->datePlantation;
    }

    public function setDatePlantation(?\DateTime $datePlantation): static
    {
        $this->datePlantation = $datePlantation;
        return $this;
    }

    public function getDateRecoltePrevue(): ?\DateTime
    {
        return $this->dateRecoltePrevue;
    }

    public function setDateRecoltePrevue(?\DateTime $dateRecoltePrevue): static
    {
        $this->dateRecoltePrevue = $dateRecoltePrevue;
        return $this;
    }

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(?string $statut): static
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

    public function getConsommations(): Collection
    {
        return $this->consommations;
    }

    public function addConsommation(Consommation $consommation): static
    {
        if (!$this->consommations->contains($consommation)) {
            $this->consommations->add($consommation);
            $consommation->setCulture($this);
        }
        return $this;
    }

    public function removeConsommation(Consommation $consommation): static
    {
        if ($this->consommations->removeElement($consommation)) {
            if ($consommation->getCulture() === $this) {
                $consommation->setCulture(null);
            }
        }
        return $this;
    }
}