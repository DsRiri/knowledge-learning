<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * User entity
 * Represents platform users with authentication and roles
 * Implements Symfony Security interfaces for authentication
 */
#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
#[UniqueEntity(fields: ['email'], message: 'There is already an account with this email')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /** User email (used for login) */
    #[ORM\Column(length: 180, unique: true)]
    private ?string $email = null;

    /** Hashed password */
    #[ORM\Column]
    private ?string $password = null;

    /** Account activation status: false until email confirmation */
    #[ORM\Column]
    private bool $isActive = false;

    /** Email verification status */
    #[ORM\Column]
    private bool $isVerified = false;

    /** Token for email activation */
    #[ORM\Column(length: 100, nullable: true)]
    private ?string $activationToken = null;

    /** User role: admin or client */
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Role $role = null;

    /** Creation timestamp */
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $createdAt = null;

    /** Last update timestamp */
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $updatedAt = null;

    /** User who created this record */
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $createdBy = null;

    /** User who last updated this record */
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $updatedBy = null;

    /** User's purchases */
    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Purchase::class)]
    private Collection $purchases;

    /** User's lesson validations */
    #[ORM\OneToMany(mappedBy: 'user', targetEntity: UserLesson::class)]
    private Collection $userLessons;

    /** User's certifications */
    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Certification::class)]
    private Collection $certifications;

    public function __construct()
    {
        $this->purchases = new ArrayCollection();
        $this->userLessons = new ArrayCollection();
        $this->certifications = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }
    public function getEmail(): ?string { return $this->email; }
    public function setEmail(string $email): static { $this->email = $email; return $this; }
    public function getPassword(): ?string { return $this->password; }
    public function setPassword(string $password): static { $this->password = $password; return $this; }
    public function isActive(): bool { return $this->isActive; }
    public function setIsActive(bool $isActive): static { $this->isActive = $isActive; return $this; }
    public function isVerified(): bool { return $this->isVerified; }
    public function setIsVerified(bool $isVerified): static { $this->isVerified = $isVerified; return $this; }
    public function getActivationToken(): ?string { return $this->activationToken; }
    public function setActivationToken(?string $activationToken): static { $this->activationToken = $activationToken; return $this; }
    public function getRole(): ?Role { return $this->role; }
    public function setRole(?Role $role): static { $this->role = $role; return $this; }
    public function getCreatedAt(): ?\DateTimeImmutable { return $this->createdAt; }
    public function setCreatedAt(\DateTimeImmutable $createdAt): static { $this->createdAt = $createdAt; return $this; }
    public function getUpdatedAt(): ?\DateTimeImmutable { return $this->updatedAt; }
    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static { $this->updatedAt = $updatedAt; return $this; }
    public function getCreatedBy(): ?string { return $this->createdBy; }
    public function setCreatedBy(?string $createdBy): static { $this->createdBy = $createdBy; return $this; }
    public function getUpdatedBy(): ?string { return $this->updatedBy; }
    public function setUpdatedBy(?string $updatedBy): static { $this->updatedBy = $updatedBy; return $this; }
    public function getPurchases(): Collection { return $this->purchases; }
    public function getUserLessons(): Collection { return $this->userLessons; }
    public function getCertifications(): Collection { return $this->certifications; }

    /**
     * Returns the roles granted to the user
     * Converts role name to Symfony security role format (ROLE_ADMIN, ROLE_CLIENT)
     */
    public function getRoles(): array
    {
        $roleName = $this->role?->getName() ?? 'client';
        return ['ROLE_' . strtoupper($roleName)];
    }

    public function eraseCredentials(): void {}
    public function getUserIdentifier(): string { return $this->email; }
}