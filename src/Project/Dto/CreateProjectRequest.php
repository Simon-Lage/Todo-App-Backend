<?php

declare(strict_types=1);

namespace App\Project\Dto;

use App\Http\Request\JsonRequestDto;
use Symfony\Component\Validator\Constraints as Assert;

final class CreateProjectRequest implements JsonRequestDto
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(max: 255)]
        public readonly string $name,
        #[Assert\Length(max: 65535)]
        public readonly ?string $description
    ) {
    }

    public static function fromArray(array $payload): static
    {
        if (!array_key_exists('name', $payload)) {
            throw new \InvalidArgumentException('Name is required.');
        }

        $description = array_key_exists('description', $payload) ? (string) $payload['description'] : null;

        return new self((string) $payload['name'], $description);
    }
}
