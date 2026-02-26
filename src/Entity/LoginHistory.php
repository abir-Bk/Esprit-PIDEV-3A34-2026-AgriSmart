<?php

namespace App\Entity;

use App\Repository\LoginHistoryRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LoginHistoryRepository::class)]
class LoginHistory
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'loginTime')]
    private ?User $user = null;

    #[ORM\Column]
    private ?\DateTime $loginTime = null;

    #[ORM\Column(length: 255)]
    private ?string $ipAddress = null;

    #[ORM\Column(length: 255)]
    private ?string $userAgent = null;

    #[ORM\Column(length: 20)]
    private ?string $status = null;
    #[ORM\Column(type: 'string', length: 180, nullable: true)]
    private ?string $attemptedEmail = null;

    #[ORM\Column(type: 'string', length: 200, nullable: true)]
    private ?string $country = null;
    #[ORM\Column(type: 'boolean')]
private bool $suspicious = false;

public function isSuspicious(): bool
{
    return $this->suspicious;
}

public function setSuspicious(bool $suspicious): self
{
    $this->suspicious = $suspicious;
    return $this;
}
public function getCountry(): ?string
    {
        return $this->country;
    }

    public function setCountry(?string $country): self
    {
        $this->country = $country;
        return $this;
    }

    public function getAttemptedEmail(): ?string
    {
        return $this->attemptedEmail;
    }

    public function setAttemptedEmail(?string $email): self
    {
        $this->attemptedEmail = $email;
        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getLoginTime(): ?\DateTime
    {
        return $this->loginTime;
    }

    public function setLoginTime(\DateTime $loginTime): static
    {
        $this->loginTime = $loginTime;

        return $this;
    }

    public function getIpAddress(): ?string
    {
        return $this->ipAddress;
    }

    public function setIpAddress(string $ipAddress): static
    {
        $this->ipAddress = $ipAddress;

        return $this;
    }

    public function getUserAgent(): ?string
    {
        return $this->userAgent;
    }

    public function setUserAgent(string $userAgent): static
    {
        $this->userAgent = $userAgent;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }
}
