<?php

namespace App\Entity;

use App\Repository\AdminNotificationRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AdminNotificationRepository::class)]
class AdminNotification
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private string $title;

    #[ORM\Column(type: 'text')]
    private string $message;

    #[ORM\Column(length: 50)]
    private string $type = 'info'; // info | warning | success

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $link = null;  // URL to redirect on click

    #[ORM\Column]
    private bool $isRead = false;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\ManyToOne]
    private ?User $relatedUser = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getTitle(): string { return $this->title; }
    public function setTitle(string $t): self { $this->title = $t; return $this; }

    public function getMessage(): string { return $this->message; }
    public function setMessage(string $m): self { $this->message = $m; return $this; }

    public function getType(): string { return $this->type; }
    public function setType(string $t): self { $this->type = $t; return $this; }

    public function getLink(): ?string { return $this->link; }
    public function setLink(?string $l): self { $this->link = $l; return $this; }

    public function isRead(): bool { return $this->isRead; }
    public function setIsRead(bool $r): self { $this->isRead = $r; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }

    public function getRelatedUser(): ?User { return $this->relatedUser; }
    public function setRelatedUser(?User $u): self { $this->relatedUser = $u; return $this; }
}