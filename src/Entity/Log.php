<?php

namespace App\Entity;

use App\Repository\LogRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: LogRepository::class)]
#[ORM\Table(name: 'logs')]
class Log
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private ?Uuid $id = null;

    #[ORM\Column(length: 255)]
    private ?string $action = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $performed_by_user = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $performed_at = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $details = null;

    public function __construct()
    {
        $this->id = Uuid::v4();
        $this->performed_at = new \DateTimeImmutable();
    }

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getAction(): ?string
    {
        return $this->action;
    }

    public function setAction(string $action): static
    {
        $this->action = $action;
        return $this;
    }

    public function getPerformedByUser(): ?User
    {
        return $this->performed_by_user;
    }

    public function setPerformedByUser(?User $performed_by_user): static
    {
        $this->performed_by_user = $performed_by_user;
        return $this;
    }

    public function getPerformedAt(): ?\DateTimeImmutable
    {
        return $this->performed_at;
    }

    public function setPerformedAt(\DateTimeImmutable $performed_at): static
    {
        $this->performed_at = $performed_at;
        return $this;
    }

    public function getDetails(): ?string
    {
        return $this->details;
    }

    public function setDetails(?string $details): static
    {
        $this->details = $details;
        return $this;
    }

    public function getDetailsDecoded(): ?array
    {
        if ($this->details === null) {
            return null;
        }

        $decoded = json_decode($this->details, true);

        return is_array($decoded) ? $decoded : null;
    }
}
