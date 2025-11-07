<?php

declare(strict_types=1);

namespace App\User\Dto;

use App\Http\Request\JsonRequestDto;
use Symfony\Component\Validator\Constraints as Assert;

final class VerifyPasswordResetEmailRequest implements JsonRequestDto
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Email]
        public readonly string $email
    ) {
    }

    public static function fromArray(array $payload): static
    {
        if (!array_key_exists('email', $payload)) {
            throw new \InvalidArgumentException('Email is required.');
        }

        return new self((string) $payload['email']);
    }
}
