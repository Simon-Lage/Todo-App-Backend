<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\AppConfigRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: AppConfigRepository::class)]
#[ORM\Table(name: 'app_config')]
class AppConfig
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private ?Uuid $id = null;

    #[ORM\Column(type: Types::JSON)]
    private array $allowed_email_domains = [];

    public function __construct(array $allowedEmailDomains = [])
    {
        $this->id = Uuid::v4();
        $this->allowed_email_domains = array_values(array_map('strtolower', $allowedEmailDomains));
    }

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    /**
     * @return string[]
     */
    public function getAllowedEmailDomains(): array
    {
        return $this->allowed_email_domains;
    }

    /**
     * @param string[] $domains
     */
    public function setAllowedEmailDomains(array $domains): self
    {
        $this->allowed_email_domains = array_values(array_map('strtolower', $domains));

        return $this;
    }
}
