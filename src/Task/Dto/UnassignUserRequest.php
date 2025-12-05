<?php

declare(strict_types=1);

namespace App\Task\Dto;

use App\Http\Request\JsonRequestDto;
use Symfony\Component\Validator\Constraints as Assert;

final class UnassignUserRequest implements JsonRequestDto
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Uuid]
        public readonly string $userId
    ) {
    }

    public static function fromArray(array $payload): static
    {
        if (!array_key_exists('user_id', $payload)) {
            throw new \InvalidArgumentException('user_id is required.');
        }

        return new self((string) $payload['user_id']);
    }
}

