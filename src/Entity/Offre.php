<?php

namespace App\Entity;

use App\Repository\OffreRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity(repositoryClass: OffreRepository::class)]
class Offre
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    /** @phpstan-ignore-next-line */
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\NotBlank(message: "Le titre est obligatoire")]
    #[Assert\Length(min: 5, minMessage: "Le titre doit faire au moins {{ limit }} caractères")]
    private ?string $title = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\NotBlank(message: "Le type de poste est obligatoire")]
    #[Assert\Length(min: 3, minMessage: "Le type de poste est trop court")]
    #[Assert\Regex(pattern: "/^[a-zA-ZÀ-ÿ\s\-]+$/", message: "Le type de poste ne doit contenir que des lettres")]
    private ?string $typePoste = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\NotBlank(message: "Le type de contrat est obligatoire")]
    private ?string $typeContrat = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\NotBlank(message: "La description est obligatoire")]
    #[Assert\Length(min: 10, minMessage: "La description doit faire au moins {{ limit }} caractères")]
    private ?string $description = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\NotBlank(message: "Le lieu est obligatoire")]
    #[Assert\Length(min: 3, minMessage: "Le nom du lieu est trop court")]
    private ?string $lieu = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\NotBlank(message: "Le statut est obligatoire")]
    private ?string $statut = null;

    #[ORM\Column(type: "datetime_immutable", nullable: true)]
    #[Assert\NotNull(message: "La date de début est obligatoire")]
    #[Assert\GreaterThanOrEqual("today", message: "La date de début ne peut pas être dans le passé")]
    private ?\DateTimeImmutable $dateDebut = null;

    #[ORM\Column(type: "datetime_immutable", nullable: true)]
    #[Assert\NotNull(message: "La date de fin est obligatoire")]
    private ?\DateTimeImmutable $dateFin = null;

    #[ORM\Column(nullable: true)]
    #[Assert\NotNull(message: "Le salaire est obligatoire")]
    #[Assert\Positive(message: "Le salaire doit être un nombre positif")]
    private ?float $salaire = null;

    /**
     * @var Collection<int, Demande>
     */
    #[ORM\OneToMany(
        targetEntity: Demande::class, 
        mappedBy: 'offre', 
        cascade: ['persist', 'remove'], 
        orphanRemoval: true 
    )]
    private Collection $demandes;

    #[ORM\Column(nullable: true)]
    private ?bool $isActive = null;

    /** Statut de validation par l'admin : en_attente | approuvée | refusée */
    #[ORM\Column(length: 20)]
    private string $statutValidation = 'en_attente';

    #[ORM\ManyToOne(inversedBy: 'offres')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $agriculteur = null;

    public function __construct()
    {
        $this->demandes = new ArrayCollection();
    }

    /**
     * Custom validation to compare DateDebut and DateFin
     */
    #[Assert\Callback]
    public function validateDates(ExecutionContextInterface $context): void
    {
        if ($this->dateDebut !== null && $this->dateFin !== null) {
            if ($this->dateFin <= $this->dateDebut) {
                $context->buildViolation('La date de fin doit être strictement supérieure à la date de début.')
                    ->atPath('dateFin')
                    ->addViolation();
            }
        }
    }

    // --- GETTERS & SETTERS ---

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): static
    {
        $this->title = $title;
        return $this;
    }

    public function getTypePoste(): ?string
    {
        return $this->typePoste;
    }

    public function setTypePoste(?string $typePoste): static
    {
        $this->typePoste = $typePoste;
        return $this;
    }

    public function getTypeContrat(): ?string
    {
        return $this->typeContrat;
    }

    public function setTypeContrat(?string $typeContrat): static
    {
        $this->typeContrat = $typeContrat;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getLieu(): ?string
    {
        return $this->lieu;
    }

    public function setLieu(?string $lieu): static
    {
        $this->lieu = $lieu;
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

    public function getDateDebut(): ?\DateTimeImmutable
    {
        return $this->dateDebut;
    }

    public function setDateDebut(?\DateTimeImmutable $dateDebut): static
    {
        $this->dateDebut = $dateDebut;
        return $this;
    }

    public function getDateFin(): ?\DateTimeImmutable
    {
        return $this->dateFin;
    }

    public function setDateFin(?\DateTimeImmutable $dateFin): static
    {
        $this->dateFin = $dateFin;
        return $this;
    }

    public function getSalaire(): ?float
    {
        return $this->salaire;
    }

    public function setSalaire(?float $salaire): static
    {
        $this->salaire = $salaire;
        return $this;
    }

    /** @return Collection<int, Demande> */
    public function getDemandes(): Collection
    {
        return $this->demandes;
    }

    public function addDemande(Demande $demande): static
    {
        if (!$this->demandes->contains($demande)) {
            $this->demandes->add($demande);
            $demande->setOffre($this);
        }
        return $this;
    }

    public function removeDemande(Demande $demande): static
    {
        if ($this->demandes->removeElement($demande)) {
            if ($demande->getOffre() === $this) {
                $demande->setOffre(null);
            }
        }
        return $this;
    }

    public function getIsActive(): ?bool
    {
        return $this->isActive;
    }

    public function setIsActive(?bool $isActive): self
    {
        $this->isActive = $isActive;
        return $this;
    }

    public function getStatutValidation(): string
    {
        return $this->statutValidation;
    }

    public function setStatutValidation(string $statutValidation): static
    {
        $this->statutValidation = $statutValidation;
        return $this;
    }

    public function getAgriculteur(): ?User
    {
        return $this->agriculteur;
    }

    public function setAgriculteur(?User $agriculteur): static
    {
        $this->agriculteur = $agriculteur;
        return $this;
    }
}