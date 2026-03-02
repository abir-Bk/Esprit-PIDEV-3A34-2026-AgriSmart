<?php

namespace App\Entity;

use App\Repository\DemandeRepository;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\User;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: DemandeRepository::class)]
class Demande
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    /** @phpstan-ignore-next-line */
    private ?int $id = null;

#[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Le nom est obligatoire")]
    #[Assert\Length(
        min: 3, 
        minMessage: "Le nom doit contenir au moins {{ limit }} caractères"
    )]
    #[Assert\Regex(
        pattern: "/^[a-zA-ZÀ-ÿ\s\-]+$/",
        message: "Le nom ne doit contenir que des lettres"
    )]
    private ?string $nom = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Le prénom est obligatoire")]
    #[Assert\Length(
        min: 3, 
        minMessage: "Le prénom doit contenir au moins {{ limit }} caractères"
    )]
    #[Assert\Regex(
        pattern: "/^[a-zA-ZÀ-ÿ\s\-]+$/",
        message: "Le prénom ne doit contenir que des lettres"
    )]
    private ?string $prenom = null;


#[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Le numéro de téléphone est obligatoire")]
    #[Assert\Length(
        min: 8, 
        max: 8, 
        exactMessage: "Le numéro de téléphone doit contenir exactement {{ limit }} chiffres"
    )]
    #[Assert\Regex(
        pattern: "/^[2-9][0-9]{7}$/",
        message: "Le numéro de téléphone doit commencer par un chiffre entre 2 et 9 (ex: de 20 à 99...) et contenir 8 chiffres au total."
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

    #[ORM\Column(nullable: true)]
    private ?int $score = null;

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

    public function getScore(): ?int
    {
        return $this->score;
    }

    public function setScore(?int $score): static
    {
        $this->score = $score;

        return $this;
    }
}
