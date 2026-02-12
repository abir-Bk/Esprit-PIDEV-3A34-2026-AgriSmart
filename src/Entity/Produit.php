<?php

namespace App\Entity;

use App\Repository\ProduitRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity(repositoryClass: ProduitRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Produit
{
    public const TYPE_VENTE = 'vente';
    public const TYPE_LOCATION = 'location';

    public const CATEGORIES = [
        'Légumes' => 'legumes',
        'Fruits' => 'fruits',
        'Céréales' => 'cereales',
        'Engrais' => 'engrais',
        'Semences' => 'semences',
        'Matériel' => 'materiel',
        'Irrigation' => 'irrigation',
        'Services' => 'services',
        'Autre' => 'autre',
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le nom est obligatoire.')]
    #[Assert\Length(max: 255)]
    private ?string $nom = null;


    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: 'Veuillez décrire votre produit.')]
    #[Assert\Length(min: 10, minMessage: 'La description doit faire au moins 10 caractères.')]
    private ?string $description = null;

    #[ORM\Column(length: 20)]
    #[Assert\NotBlank(message: 'Le type est obligatoire.')]
    #[Assert\Choice(choices: [self::TYPE_VENTE, self::TYPE_LOCATION], message: 'Type invalide.')]
    private ?string $type = null;

    #[ORM\Column]
    #[Assert\NotNull(message: 'Le prix est obligatoire.')]
    #[Assert\PositiveOrZero(message: 'Le prix doit être ≥ 0.')]
    private ?float $prix = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank(message: 'La catégorie est obligatoire.')]
    #[Assert\Choice(callback: 'getCategorieValues', message: 'Catégorie invalide.')]
    private ?string $categorie = null;

    #[ORM\Column]
    #[Assert\NotNull(message: 'Le stock est obligatoire.')]
    #[Assert\PositiveOrZero(message: 'Le stock doit être ≥ 0.')]
    private ?int $quantiteStock = null;


    #[ORM\Column(length: 255, nullable: true)]
    private ?string $image = null;

    #[ORM\Column(options: ['default' => false])]
    private bool $isPromotion = false;

    #[ORM\Column(nullable: true)]
    #[Assert\PositiveOrZero(message: 'Le prix promo doit être ≥ 0.')]
    private ?float $promotionPrice = null;


    #[ORM\Column(length: 255, nullable: true)]
    private ?string $locationAddress = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    #[Assert\GreaterThanOrEqual('today', message: 'La date de début ne peut pas être passée.')]
    private ?\DateTimeImmutable $locationStart = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $locationEnd = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'produits')]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $vendeur = null;

    public function __construct()
    {
    }

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $now = new \DateTimeImmutable();
        $this->createdAt = $now;
        $this->updatedAt = $now;
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public static function getCategorieValues(): array
    {
        return array_values(self::CATEGORIES);
    }

    public function getPrixEffectif(): ?float
    {
        if ($this->isPromotion && $this->promotionPrice !== null) {
            return $this->promotionPrice;
        }
        return $this->prix;
    }

    #[Assert\Callback]
    public function validateBusinessRules(ExecutionContextInterface $context): void
    {
        // Promo
        if ($this->isPromotion) {
            if ($this->promotionPrice === null) {
                $context->buildViolation('Prix promo obligatoire si promotion activée.')
                    ->atPath('promotionPrice')
                    ->addViolation();
            } elseif ($this->prix !== null && $this->promotionPrice > $this->prix) {
                $context->buildViolation('Le prix promo doit être ≤ prix normal.')
                    ->atPath('promotionPrice')
                    ->addViolation();
            }
        }

        // Location
        if ($this->type === self::TYPE_LOCATION) {
            if ($this->locationStart === null) {
                $context->buildViolation('Date de disponibilité obligatoire pour la location.')
                    ->atPath('locationStart')
                    ->addViolation();
            }
            if ($this->locationAddress === null || trim($this->locationAddress) === '') {
                $context->buildViolation("L'emplacement (Ville/Région) est obligatoire pour la location.")
                    ->atPath('locationAddress')
                    ->addViolation();
            }
            if ($this->locationStart && $this->locationEnd && $this->locationEnd < $this->locationStart) {
                $context->buildViolation('La date fin doit être ≥ date début.')
                    ->atPath('locationEnd')
                    ->addViolation();
            }
        }
    }

    // --- GETTERS & SETTERS ---

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }
    public function setNom(string $nom): static
    {
        $this->nom = $nom;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }
    public function setDescription(string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }
    public function setType(string $type): static
    {
        $this->type = $type;
        return $this;
    }

    public function getPrix(): ?float
    {
        return $this->prix;
    }
    public function setPrix(float $prix): static
    {
        $this->prix = $prix;
        return $this;
    }

    public function getCategorie(): ?string
    {
        return $this->categorie;
    }
    public function setCategorie(string $categorie): static
    {
        $this->categorie = $categorie;
        return $this;
    }

    public function getQuantiteStock(): ?int
    {
        return $this->quantiteStock;
    }
    public function setQuantiteStock(int $quantiteStock): static
    {
        $this->quantiteStock = $quantiteStock;
        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }
    public function setImage(?string $image): static
    {
        $this->image = $image;
        return $this;
    }

    public function isPromotion(): bool
    {
        return $this->isPromotion;
    }
    public function setIsPromotion(bool $isPromotion): static
    {
        $this->isPromotion = $isPromotion;
        return $this;
    }

    public function getPromotionPrice(): ?float
    {
        return $this->promotionPrice;
    }
    public function setPromotionPrice(?float $promotionPrice): static
    {
        $this->promotionPrice = $promotionPrice;
        return $this;
    }

    public function getLocationAddress(): ?string
    {
        return $this->locationAddress;
    }
    public function setLocationAddress(?string $locationAddress): static
    {
        $this->locationAddress = $locationAddress;
        return $this;
    }

    public function getLocationStart(): ?\DateTimeImmutable
    {
        return $this->locationStart;
    }
    public function setLocationStart(?\DateTimeImmutable $locationStart): static
    {
        $this->locationStart = $locationStart;
        return $this;
    }

    public function getLocationEnd(): ?\DateTimeImmutable
    {
        return $this->locationEnd;
    }
    public function setLocationEnd(?\DateTimeImmutable $locationEnd): static
    {
        $this->locationEnd = $locationEnd;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }
    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function getVendeur(): ?User
    {
        return $this->vendeur;
    }
    public function setVendeur(?User $vendeur): static
    {
        $this->vendeur = $vendeur;
        return $this;
    }
}
