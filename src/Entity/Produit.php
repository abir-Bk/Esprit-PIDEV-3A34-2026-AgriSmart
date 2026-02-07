<?php

namespace App\Entity;

use App\Repository\ProduitRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity(repositoryClass: ProduitRepository::class)]
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

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le type est obligatoire.')]
    #[Assert\Choice(choices: [self::TYPE_VENTE, self::TYPE_LOCATION], message: 'Type invalide.')]
    private ?string $type = null;

    #[ORM\Column]
    #[Assert\NotNull(message: 'Le prix est obligatoire.')]
    #[Assert\PositiveOrZero(message: 'Le prix doit être ≥ 0.')]
    private ?float $prix = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'La catégorie est obligatoire.')]
    #[Assert\Choice(callback: 'getCategorieValues', message: 'Catégorie invalide.')]
    private ?string $categorie = null;

    #[ORM\Column]
    #[Assert\NotNull(message: 'Le stock est obligatoire.')]
    #[Assert\PositiveOrZero(message: 'Le stock doit être ≥ 0.')]
    private ?int $quantiteStock = null;

    // image = soit URL, soit nom de fichier uploadé (ex: uploads/produits/xxx.jpg)
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $image = null;

    #[ORM\Column(options: ['default' => false])]
    private ?bool $isPromotion = false;

    #[ORM\Column(nullable: true)]
    #[Assert\PositiveOrZero(message: 'Le prix promo doit être ≥ 0.')]
    private ?float $promotionPrice = null;

    // Location only
    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $locationStart = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $locationEnd = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $locationAddress = null;

    /**
     * @var Collection<int, Commande>
     */
    #[ORM\OneToMany(targetEntity: Commande::class, mappedBy: 'produit')]
    private Collection $commandes;

    public function __construct()
    {
        $this->commandes = new ArrayCollection();
    }

    public static function getCategorieValues(): array
    {
        return array_values(self::CATEGORIES);
    }

    #[Assert\Callback]
    public function validateBusinessRules(ExecutionContextInterface $context): void
    {
        // promo logic
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
        } else {
            // if no promo, ignore promo price
            if ($this->promotionPrice !== null) {
                $context->buildViolation('Désactivez la promo ou videz le prix promo.')
                    ->atPath('promotionPrice')
                    ->addViolation();
            }
        }

        // location logic
        if ($this->type === self::TYPE_LOCATION) {
            if ($this->locationStart === null) {
                $context->buildViolation('Date de disponibilité obligatoire pour la location.')
                    ->atPath('locationStart')
                    ->addViolation();
            }
            if ($this->locationAddress === null || trim($this->locationAddress) === '') {
                $context->buildViolation('Emplacement obligatoire pour la location.')
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

    public function getId(): ?int { return $this->id; }

    public function getNom(): ?string { return $this->nom; }
    public function setNom(string $nom): static { $this->nom = $nom; return $this; }

    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): static { $this->description = $description; return $this; }

    public function getType(): ?string { return $this->type; }
    public function setType(string $type): static { $this->type = $type; return $this; }

    public function getPrix(): ?float { return $this->prix; }
    public function setPrix(float $prix): static { $this->prix = $prix; return $this; }

    public function getCategorie(): ?string { return $this->categorie; }
    public function setCategorie(string $categorie): static { $this->categorie = $categorie; return $this; }

    public function getQuantiteStock(): ?int { return $this->quantiteStock; }
    public function setQuantiteStock(int $quantiteStock): static { $this->quantiteStock = $quantiteStock; return $this; }

    public function getImage(): ?string { return $this->image; }
    public function setImage(?string $image): static { $this->image = $image; return $this; }

    public function isPromotion(): ?bool { return $this->isPromotion; }
    public function setIsPromotion(bool $isPromotion): static { $this->isPromotion = $isPromotion; return $this; }

    public function getPromotionPrice(): ?float { return $this->promotionPrice; }
    public function setPromotionPrice(?float $promotionPrice): static { $this->promotionPrice = $promotionPrice; return $this; }

    public function getLocationStart(): ?\DateTimeImmutable { return $this->locationStart; }
    public function setLocationStart(?\DateTimeImmutable $locationStart): static { $this->locationStart = $locationStart; return $this; }

    public function getLocationEnd(): ?\DateTimeImmutable { return $this->locationEnd; }
    public function setLocationEnd(?\DateTimeImmutable $locationEnd): static { $this->locationEnd = $locationEnd; return $this; }

    public function getLocationAddress(): ?string { return $this->locationAddress; }
    public function setLocationAddress(?string $locationAddress): static { $this->locationAddress = $locationAddress; return $this; }

    /**
     * @return Collection<int, Commande>
     */
    public function getCommandes(): Collection { return $this->commandes; }

    public function addCommande(Commande $commande): static
    {
        if (!$this->commandes->contains($commande)) {
            $this->commandes->add($commande);
            $commande->setProduit($this);
        }
        return $this;
    }

    public function removeCommande(Commande $commande): static
    {
        if ($this->commandes->removeElement($commande)) {
            if ($commande->getProduit() === $this) {
                $commande->setProduit(null);
            }
        }
        return $this;
    }
}
