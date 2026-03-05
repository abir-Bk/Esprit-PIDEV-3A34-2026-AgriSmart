<?php

namespace App\Entity;

use App\Repository\ParcelleRepository;
use App\Entity\User;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ParcelleRepository::class)]
class Parcelle
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Le nom de la parcelle est obligatoire.")]
    #[Assert\Length(
        min: 3,
        max: 100,
        minMessage: "Le nom doit contenir au moins {{ limit }} caractères.",
        maxMessage: "Le nom ne peut pas dépasser {{ limit }} caractères."
    )]
    private ?string $nom = null;

    #[ORM\Column]
    #[Assert\NotBlank(message: "La surface est obligatoire.")]
    #[Assert\Positive(message: "La surface doit être un nombre positif.")]
    #[Assert\LessThan(value: 10000, message: "La surface ne peut pas dépasser 10 000 hectares.")]
    private ?float $surface = null;

    #[ORM\Column]
    #[Assert\NotBlank(message: "La latitude est obligatoire.")]
    #[Assert\Range(
        min: -90,
        max: 90,
        notInRangeMessage: "La latitude doit être comprise entre -90 et 90 degrés."
    )]
    private ?float $latitude = null;

    #[ORM\Column]
    #[Assert\NotBlank(message: "La longitude est obligatoire.")]
    #[Assert\Range(
        min: -180,
        max: 180,
        notInRangeMessage: "La longitude doit être comprise entre -180 et 180 degrés."
    )]
    private ?float $longitude = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Le type de sol est obligatoire.")]
    #[Assert\Choice(
        choices: ["Argileux", "Sableux", "Limoneux", "Calcaire", "Tourbeux", "Humifère"],
        message: "Veuillez sélectionner un type de sol valide."
    )]
    private ?string $typeSol = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    /** @var Collection<int, Culture> */
    #[ORM\OneToMany(targetEntity: Culture::class, mappedBy: 'parcelle')]
    private Collection $cultures;

    public function __construct()
    {
        $this->cultures = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(?string $nom): static
    {
        $this->nom = $nom;
        return $this;
    }

    public function getSurface(): ?float
    {
        return $this->surface;
    }

    public function setSurface(?float $surface): static
    {
        $this->surface = $surface;
        return $this;
    }

    public function getLatitude(): ?float
    {
        return $this->latitude;
    }

    public function setLatitude(?float $latitude): static
    {
        $this->latitude = $latitude;
        return $this;
    }

    public function getLongitude(): ?float
    {
        return $this->longitude;
    }

    public function setLongitude(?float $longitude): static
    {
        $this->longitude = $longitude;
        return $this;
    }

    public function getTypeSol(): ?string
    {
        return $this->typeSol;
    }

    public function setTypeSol(?string $typeSol): static
    {
        $this->typeSol = $typeSol;
        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;
        return $this;
    }

    /** @return Collection<int, Culture> */
    public function getCultures(): Collection
    {
        return $this->cultures;
    }

    public function addCulture(Culture $culture): static
    {
        if (!$this->cultures->contains($culture)) {
            $this->cultures->add($culture);
            $culture->setParcelle($this);
        }
        return $this;
    }

    public function removeCulture(Culture $culture): static
    {
        if ($this->cultures->removeElement($culture)) {
            if ($culture->getParcelle() === $this) {
                $culture->setParcelle(null);
            }
        }
        return $this;
    }
}