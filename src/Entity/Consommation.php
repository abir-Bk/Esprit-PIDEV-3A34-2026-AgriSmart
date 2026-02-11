<?php

namespace App\Entity;

use App\Repository\ConsommationRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ConsommationRepository::class)]
class Consommation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?float $quantite = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $dateConsommation = null;

    #[ORM\ManyToOne(inversedBy: 'consommations')]
    private ?Ressource $ressource = null;

    #[ORM\ManyToOne(inversedBy: 'consommations')]
    private ?Culture $culture = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getQuantite(): ?float
    {
        return $this->quantite;
    }

    public function setQuantite(float $quantite): static
    {
        $this->quantite = $quantite;

        return $this;
    }

    public function getDateConsommation(): ?\DateTimeImmutable
    {
        return $this->dateConsommation;
    }

    public function setDateConsommation(\DateTimeImmutable $dateConsommation): static
    {
        $this->dateConsommation = $dateConsommation;

        return $this;
    }

    public function getRessource(): ?Ressource
    {
        return $this->ressource;
    }

    public function setRessource(?Ressource $ressource): static
    {
        $this->ressource = $ressource;

        return $this;
    }

    public function getCulture(): ?Culture
    {
        return $this->culture;
    }

    public function setCulture(?Culture $culture): static
    {
        $this->culture = $culture;

        return $this;
    }
}
