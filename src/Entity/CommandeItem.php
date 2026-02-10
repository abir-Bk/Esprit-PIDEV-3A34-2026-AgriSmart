<?php

namespace App\Entity;

use App\Repository\CommandeItemRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CommandeItemRepository::class)]
class CommandeItem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'items')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Commande $commande = null;

    // Produit acheté
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Produit $produit = null;

    #[ORM\Column]
    private int $quantite = 1;

    // Snapshot prix au moment de commande
    #[ORM\Column]
    private float $prixUnitaire = 0.0;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCommande(): ?Commande
    {
        return $this->commande;
    }
    public function setCommande(Commande $commande): static
    {
        $this->commande = $commande;
        return $this;
    }

    public function getProduit(): ?Produit
    {
        return $this->produit;
    }
    public function setProduit(Produit $produit): static
    {
        $this->produit = $produit;
        return $this;
    }

    public function getQuantite(): int
    {
        return $this->quantite;
    }
    public function setQuantite(int $quantite): static
    {
        $this->quantite = max(1, $quantite);
        return $this;
    }

    public function getPrixUnitaire(): float
    {
        return $this->prixUnitaire;
    }
    public function setPrixUnitaire(float $prixUnitaire): static
    {
        $this->prixUnitaire = max(0, $prixUnitaire);
        return $this;
    }

    public function getSousTotal(): float
    {
        return $this->prixUnitaire * $this->quantite;
    }
}
