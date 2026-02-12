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
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Le titre est obligatoire")]
    private ?string $title = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Le type de poste est obligatoire")]
    private ?string $typePoste = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Le type de contrat est obligatoire")]
    private ?string $typeContrat = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "La description est obligatoire")]
    private ?string $description = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Le lieu est obligatoire")]
    private ?string $lieu = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Le statut est obligatoire")]
    private ?string $statut = null;

    #[ORM\Column(type: "datetime")]
    #[Assert\NotNull(message: "La date de début est obligatoire")]
    #[Assert\GreaterThanOrEqual("today", message: "La date de début ne peut pas être dans le passé")]
    private ?\DateTime $dateDebut = null;

    #[ORM\Column(type: "datetime")]
    #[Assert\NotNull(message: "La date de fin est obligatoire")]
    private ?\DateTime $dateFin = null;

    #[ORM\Column]
    #[Assert\NotNull(message: "Le salaire est obligatoire")]
    #[Assert\Positive(message: "Le salaire doit être un nombre positif")]
    private ?float $salaire = null;

    /**
     * @var Collection<int, Demande>
     */
    #[ORM\OneToMany(targetEntity: Demande::class, mappedBy: 'offre', cascade: ['remove'])]
    private Collection $demandes;

    #[ORM\Column]
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

    // --- GETTERS & SETTERS (All Setters are now Nullable) ---

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

    public function getDateDebut(): ?\DateTime
    {
        return $this->dateDebut;
    }

    public function setDateDebut(?\DateTime $dateDebut): static
    {
        $this->dateDebut = $dateDebut;
        return $this;
    }

    public function getDateFin(): ?\DateTime
    {
        return $this->dateFin;
    }

    public function setDateFin(?\DateTime $dateFin): static
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

    /**
     * @return Collection<int, Demande>
     */
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

    public function setIsActive(bool $isActive): self
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