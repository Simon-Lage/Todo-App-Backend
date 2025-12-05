<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private ?Uuid $id = null;

    #[ORM\Column(length: 32, unique: true)]
    private ?string $name = null;

    #[ORM\Column(length: 128, unique: true)]
    private ?string $email = null;

    #[ORM\Column(length: 255)]
    private ?string $password = null;

    #[ORM\Column(options: ['default' => false])]
    private ?bool $is_password_temporary = null;

    #[ORM\Column]
    private ?bool $active = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $temporary_password_created_at = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $created_at = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTime $last_login_at = null;

    #[ORM\ManyToMany(targetEntity: Role::class)]
    #[ORM\JoinTable(name: 'user_to_role')]
    private Collection $roleEntities;

    #[ORM\ManyToOne(targetEntity: Image::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Image $profile_image = null;

    public function __construct()
    {
        $this->id = Uuid::v4();
        $this->created_at = new \DateTimeImmutable();
        $this->temporary_password_created_at = new \DateTimeImmutable();
        $this->is_password_temporary = false;
        $this->roleEntities = new ArrayCollection();
        $this->active = true;
    }

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = strtolower($email);
        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;
        return $this;
    }

    public function isPasswordTemporary(): ?bool
    {
        return $this->is_password_temporary;
    }

    public function setIsPasswordTemporary(bool $is_password_temporary): static
    {
        $this->is_password_temporary = $is_password_temporary;
        return $this;
    }

    public function isActive(): ?bool
    {
        return $this->active;
    }

    public function setActive(bool $active): static
    {
        $this->active = $active;
        return $this;
    }

    public function getTemporaryPasswordCreatedAt(): ?\DateTimeImmutable
    {
        return $this->temporary_password_created_at;
    }

    public function setTemporaryPasswordCreatedAt(\DateTimeImmutable $temporary_password_created_at): static
    {
        $this->temporary_password_created_at = $temporary_password_created_at;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->created_at;
    }

    public function setCreatedAt(\DateTimeImmutable $created_at): static
    {
        $this->created_at = $created_at;
        return $this;
    }

    public function getLastLoginAt(): ?\DateTime
    {
        return $this->last_login_at;
    }

    public function setLastLoginAt(?\DateTime $last_login_at): static
    {
        $this->last_login_at = $last_login_at;
        return $this;
    }

    public function getRoleEntities(): Collection
    {
        return $this->roleEntities;
    }

    public function assignRole(Role $role): static
    {
        if (!$this->roleEntities->contains($role)) {
            $this->roleEntities->add($role);
        }
        return $this;
    }

    public function revokeRole(Role $role): static
    {
        $this->roleEntities->removeElement($role);
        return $this;
    }

    public function replaceRoles(iterable $roles): static
    {
        $this->roleEntities->clear();

        foreach ($roles as $role) {
            if ($role instanceof Role) {
                $this->roleEntities->add($role);
            }
        }

        return $this;
    }

    public function getRoles(): array
    {
        return ['ROLE_USER'];
    }

    public function getUserIdentifier(): string
    {
        return $this->email ?? '';
    }

    public function eraseCredentials(): void
    {
    }

    public function getProfileImage(): ?Image
    {
        return $this->profile_image;
    }

    public function setProfileImage(?Image $profile_image): static
    {
        $this->profile_image = $profile_image;
        return $this;
    }
}
