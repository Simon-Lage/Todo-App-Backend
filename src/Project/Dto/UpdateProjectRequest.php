<?php

declare(strict_types=1);

namespace App\Project\Dto;

use App\Http\Request\JsonRequestDto;
use Symfony\Component\Validator\Constraints as Assert;

final class UpdateProjectRequest implements JsonRequestDto
{
    public function __construct(
        #[Assert\Length(max: 255)]
        public readonly ?string $name,
        #[Assert\Length(max: 65535)]
        public readonly ?string $description
    ) {
    }

    public static function fromArray(array $payload): static
    {
        $name = array_key_exists('name', $payload) ? (string) $payload['name'] : null;
        $description = array_key_exists('description', $payload) ? (string) $payload['description'] : null;

        if ($name === null && $description === null) {
            throw new \InvalidArgumentException('No updatable fields provided.');
        }

        return new self($name, $description);
    }
}
