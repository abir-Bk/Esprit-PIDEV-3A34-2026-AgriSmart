<?php

namespace App\Entity;

use App\Repository\RessourceRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: RessourceRepository::class)]
class Ressource
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Le nom de la ressource est obligatoire.")]
    #[Assert\Length(min: 2, max: 100, minMessage: "Le nom est trop court.", maxMessage: "Le nom est trop long.")]
    private ?string $nom = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Veuillez choisir un type de ressource.")]
    private ?string $type = null;

    #[ORM\Column(name: 'stock_restan')]
    #[Assert\NotNull(message: "Le stock ne peut pas être vide.")]
    #[Assert\PositiveOrZero(message: "Le stock ne peut pas être négatif.")]
    private ?float $stockRestant = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank(message: "Veuillez choisir une unité.")]
    private ?string $unite = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    /** @var Collection<int, Consommation> */
#[ORM\OneToMany(mappedBy: 'ressource', targetEntity: Consommation::class, cascade: ['remove'], orphanRemoval: true)]
private Collection $consommations;

    public function __construct()
    {
        $this->consommations = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(?string $nom): self
    {
        $this->nom = $nom;
        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): self
    {
        $this->type = $type;
        return $this;
    }

    public function getStockRestant(): ?float
    {
        return $this->stockRestant;
    }

    public function setStockRestant(?float $stockRestant): self
    {
        $this->stockRestant = $stockRestant;
        return $this;
    }

    public function getUnite(): ?string
    {
        return $this->unite;
    }

    public function setUnite(?string $unite): self
    {
        $this->unite = $unite;
        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;
        return $this;
    }

    /** @return Collection<int, Consommation> */
    public function getConsommations(): Collection
    {
        return $this->consommations;
    }

    public function addConsommation(Consommation $consommation): self
    {
        if (!$this->consommations->contains($consommation)) {
            $this->consommations->add($consommation);
            $consommation->setRessource($this);
        }
        return $this;
    }

    public function removeConsommation(Consommation $consommation): self
    {
        if ($this->consommations->removeElement($consommation)) {
            if ($consommation->getRessource() === $this) {
                $consommation->setRessource(null);
            }
        }
        return $this;
    }
}