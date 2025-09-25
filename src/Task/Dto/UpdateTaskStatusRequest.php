<?php

declare(strict_types=1);

namespace App\Task\Dto;

use App\Http\Request\JsonRequestDto;
use Symfony\Component\Validator\Constraints as Assert;

final class UpdateTaskStatusRequest implements JsonRequestDto
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(max: 50)]
        public readonly string $status
    ) {
    }

    public static function fromArray(array $payload): static
    {
        if (!array_key_exists('status', $payload)) {
            throw new \InvalidArgumentException('Status is required.');
        }

        return new self((string) $payload['status']);
    }
}
