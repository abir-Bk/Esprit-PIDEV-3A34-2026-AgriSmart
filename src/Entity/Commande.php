<?php

namespace App\Entity;

use App\Repository\CommandeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CommandeRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Commande
{
    public const STATUT_EN_ATTENTE = 'en_attente';
    public const STATUT_PAYEE = 'payee';
    public const STATUT_ANNULEE = 'annulee';
    public const STATUT_LIVREE = 'livree';

    public const PAIEMENT_CARTE = 'carte';
    public const PAIEMENT_DOMICILE = 'domicile';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    // Client (acheteur)
    #[ORM\ManyToOne(inversedBy: 'commandes')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $client = null;

    #[ORM\Column(length: 30)]
    private string $statut = self::STATUT_EN_ATTENTE;

    #[ORM\Column(length: 30)]
    private string $modePaiement = self::PAIEMENT_DOMICILE;

    // Adresse livraison (si domicile, et même utile pour carte)
    #[ORM\Column(length: 255)]
    private string $adresseLivraison = '';

    // Total calculé (snapshot)
    #[ORM\Column]
    private float $montantTotal = 0.0;

    // Ref paiement (si carte) — tu la remplis après retour API
    #[ORM\Column(length: 120, nullable: true)]
    private ?string $paymentRef = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    /**
     * @var Collection<int, CommandeItem>
     */
    #[ORM\OneToMany(mappedBy: 'commande', targetEntity: CommandeItem::class, cascade: ['persist'], orphanRemoval: true)]
    private Collection $items;

    public function __construct()
    {
        $this->items = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function touch(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    // -------- Getters/Setters --------

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getClient(): ?User
    {
        return $this->client;
    }
    public function setClient(User $client): static
    {
        $this->client = $client;
        return $this;
    }

    public function getStatut(): string
    {
        return $this->statut;
    }
    public function setStatut(string $statut): static
    {
        $this->statut = $statut;
        return $this;
    }

    public function getModePaiement(): string
    {
        return $this->modePaiement;
    }
    public function setModePaiement(string $modePaiement): static
    {
        $this->modePaiement = $modePaiement;
        return $this;
    }

    public function getAdresseLivraison(): string
    {
        return $this->adresseLivraison;
    }
    public function setAdresseLivraison(string $adresseLivraison): static
    {
        $this->adresseLivraison = $adresseLivraison;
        return $this;
    }

    public function getMontantTotal(): float
    {
        return $this->montantTotal;
    }
    public function setMontantTotal(float $montantTotal): static
    {
        $this->montantTotal = $montantTotal;
        return $this;
    }

    public function getPaymentRef(): ?string
    {
        return $this->paymentRef;
    }
    public function setPaymentRef(?string $paymentRef): static
    {
        $this->paymentRef = $paymentRef;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    /** @return Collection<int, CommandeItem> */
    public function getItems(): Collection
    {
        return $this->items;
    }

    public function addItem(CommandeItem $item): static
    {
        if (!$this->items->contains($item)) {
            $this->items->add($item);
            $item->setCommande($this);
        }
        return $this;
    }

    public function removeItem(CommandeItem $item): static
    {
        if ($this->items->removeElement($item)) {
            // orphanRemoval=true => supprime la ligne
        }
        return $this;
    }

    // Recalcule le total depuis les items
    public function recomputeTotal(): static
    {
        $total = 0.0;
        foreach ($this->items as $item) {
            $total += $item->getPrixUnitaire() * $item->getQuantite();
        }
        $this->montantTotal = $total;
        return $this;
    }
}
