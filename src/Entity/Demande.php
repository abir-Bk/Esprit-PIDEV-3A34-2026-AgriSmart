<?php

namespace App\Entity;

use App\Repository\DemandeRepository;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\User;
// [cite: 39, 105, 106]
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: DemandeRepository::class)]
class Demande
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    // [cite: 45, 125, 130]
    #[Assert\NotBlank(message: "Le nom est obligatoire")]
    private ?string $nom = null;

    #[ORM\Column(length: 255)]
    // [cite: 45, 125, 130]
    #[Assert\NotBlank(message: "Le prénom est obligatoire")]
    private ?string $prenom = null;

    #[ORM\Column(length: 255)]
    // [cite: 45, 125, 130]
    #[Assert\NotBlank(message: "Le numéro de téléphone est obligatoire")]
    // [cite: 120, 122, 125]
    #[Assert\Length(
        min: 8, 
        max: 8, 
        exactMessage: "Le numéro de téléphone doit contenir exactement {{ limit }} chiffres"
    )]
    private ?string $phoneNumber = null;
    #[ORM\Column]
    private ?\DateTime $datePostulation = null;

    #[ORM\Column]
    private ?\DateTime $dateModification = null;

    #[ORM\Column(length: 255)]
    private ?string $cv = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: "users_id", referencedColumnName: "id", onDelete: "SET NULL")]
    private ?User $user = null;

    #[ORM\Column(length: 255)]
    private ?string $lettreMotivation = null;

    #[ORM\ManyToOne(inversedBy: 'demandes')]
    private ?Offre $offre = null;

    #[ORM\Column(length: 255)]
    private ?string $statut = null;

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

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    public function setPrenom(?string $prenom): static
    {
        $this->prenom = $prenom;

        return $this;
    }

    public function getPhoneNumber(): ?string
    {
        return $this->phoneNumber;
    }

    public function setPhoneNumber(?string $phoneNumber): static
    {
        $this->phoneNumber = $phoneNumber;

        return $this;
    }

    public function getDatePostulation(): ?\DateTime
    {
        return $this->datePostulation;
    }

    public function setDatePostulation(\DateTime $datePostulation): static
    {
        $this->datePostulation = $datePostulation;

        return $this;
    }

    public function getDateModification(): ?\DateTime
    {
        return $this->dateModification;
    }

    public function setDateModification(\DateTime $dateModification): static
    {
        $this->dateModification = $dateModification;

        return $this;
    }

    public function getCv(): ?string
    {
        return $this->cv;
    }

    public function setCv(string $cv): static
    {
        $this->cv = $cv;

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
    public function getLettreMotivation(): ?string
    {
        return $this->lettreMotivation;
    }

    public function setLettreMotivation(string $lettreMotivation): static
    {
        $this->lettreMotivation = $lettreMotivation;

        return $this;
    }

    public function getOffre(): ?Offre
    {
        return $this->offre;
    }

    public function setOffre(?Offre $offre): static
    {
        $this->offre = $offre;

        return $this;
    }



    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): static
    {
        $this->statut = $statut;

        return $this;
    }
}
