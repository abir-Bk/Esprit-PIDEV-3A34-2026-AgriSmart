<?php

namespace App\Entity;

use App\Entity\Commande;
use App\Entity\Produit;
use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Validator\Constraints as Assert;
use Vich\UploaderBundle\Mapping\Annotation as Vich;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'users')]
#[ORM\HasLifecycleCallbacks]
#[Vich\Uploadable]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    private ?string $firstName = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    private ?string $lastName = null;

    #[ORM\Column(length: 180, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Email]
    private ?string $email = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank]
    private string $role = 'agriculteur';

    #[ORM\Column]
    private string $password = '';

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $phone = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $address = null;

    // ─── Document (PDF / image) ───────────────────────────────────────
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $documentFile = null;

    #[Vich\UploadableField(mapping: 'user_documents', fileNameProperty: 'documentFile')]
    #[Assert\File(
        maxSize: '5M',
        mimeTypes: ['image/jpeg', 'image/png', 'application/pdf'],
        mimeTypesMessage: 'Veuillez uploader un fichier PDF, JPEG ou PNG valide (max 5 Mo)'
    )]
    private ?File $documentFileFile = null;

    // ─── Profile Image / Avatar ───────────────────────────────────────
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $image = null;

    #[Vich\UploadableField(mapping: 'user_images', fileNameProperty: 'image')]
    #[Assert\File(
        maxSize: '5M',
        mimeTypes: ['image/jpeg', 'image/png'],
        mimeTypesMessage: 'Veuillez uploader une image JPEG ou PNG valide (max 5 Mo)'
    )]
    private ?File $imageFile = null;

    #[ORM\Column(length: 20)]
    private string $status = 'pending';

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    #[ORM\Column(type: "string", length: 255, nullable: true)]
    private ?string $googleId = null;

    /** @var Collection<int, Commande> */
    #[ORM\OneToMany(mappedBy: 'client', targetEntity: Commande::class)]
    private Collection $commandes;

    /** @var Collection<int, Produit> */
    #[ORM\OneToMany(mappedBy: 'vendeur', targetEntity: Produit::class)]
    private Collection $produits;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->commandes = new ArrayCollection();
        $this->produits = new ArrayCollection();
    }

    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function updateTimestamps(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    // ─── Getters & Setters ────────────────────────────────────────────

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFirstName(): ?string { return $this->firstName; }
    public function setFirstName(string $firstName): static { $this->firstName = $firstName; return $this; }

    public function getLastName(): ?string { return $this->lastName; }
    public function setLastName(string $lastName): static { $this->lastName = $lastName; return $this; }

    public function getEmail(): ?string { return $this->email; }
    public function setEmail(string $email): static { $this->email = $email; return $this; }

    public function getUserIdentifier(): string { return (string) $this->email; }

    public function getRoles(): array
    {
        return match ($this->role) {
            'admin'       => ['ROLE_ADMIN'],
            'employee'    => ['ROLE_EMPLOYEE'],
            'agriculteur' => ['ROLE_AGRICULTEUR'],
            'fournisseur' => ['ROLE_FOURNISSEUR'],
            default       => ['ROLE_USER'],
        };
    }

    public function getPassword(): string { return $this->password; }
    public function setPassword(string $password): static { $this->password = $password; return $this; }

    public function eraseCredentials(): void {}

    public function getRole(): string { return $this->role; }
    public function setRole(string $role): static
    {
        $allowed = ['admin', 'employee', 'agriculteur', 'fournisseur'];
        if (!in_array($role, $allowed, true)) {
            throw new \InvalidArgumentException("Invalid role: $role");
        }
        $this->role = $role;

        $mapping = [
            'admin'       => 'active',
            'employee'    => 'active',
            'agriculteur' => 'pending',
            'fournisseur' => 'pending',
        ];
        $this->status = $mapping[$role] ?? 'pending';

        return $this;
    }

    public function getStatus(): string { return $this->status; }
    public function setStatus(string $status): static { $this->status = $status; return $this; }

    public function getPhone(): ?string { return $this->phone; }
    public function setPhone(?string $phone): static { $this->phone = $phone; return $this; }

    public function getAddress(): ?string { return $this->address; }
    public function setAddress(?string $address): static { $this->address = $address; return $this; }

    // ─── Document File Methods ────────────────────────────────────────
    public function getDocumentFile(): ?string { return $this->documentFile; }
    public function setDocumentFile(?string $documentFile): static { $this->documentFile = $documentFile; return $this; }

    public function setDocumentFileFile(?File $file = null): void
    {
        $this->documentFileFile = $file;
        if ($file) {
            $this->updatedAt = new \DateTimeImmutable();
        }
    }

    public function getDocumentFileFile(): ?File { return $this->documentFileFile; }

    // ─── Image File Methods ───────────────────────────────────────────
    public function getImage(): ?string { return $this->image; }
    public function setImage(?string $image): static { $this->image = $image; return $this; }

    public function setImageFile(?File $file = null): void
    {
        $this->imageFile = $file;
        if ($file) {
            $this->updatedAt = new \DateTimeImmutable();
        }
    }

    public function getImageFile(): ?File { return $this->imageFile; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function setCreatedAt(\DateTimeImmutable $createdAt): static { $this->createdAt = $createdAt; return $this; }

    public function getUpdatedAt(): \DateTimeImmutable { return $this->updatedAt; }
    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static { $this->updatedAt = $updatedAt; return $this; }

    public function getGoogleId(): ?string
    {
        return $this->googleId;
    }

    public function setGoogleId(?string $googleId): static
    {
        $this->googleId = $googleId;

        return $this;
    }

    /** @return Collection<int, Commande> */
    public function getCommandes(): Collection
    {
        return $this->commandes;
    }

    /** @return Collection<int, Produit> */
    public function getProduits(): Collection
    {
        return $this->produits;
    }
}