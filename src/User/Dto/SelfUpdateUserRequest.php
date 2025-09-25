<?php

declare(strict_types=1);

namespace App\User\Dto;

use App\Http\Request\JsonRequestDto;
use Symfony\Component\Validator\Constraints as Assert;

final class SelfUpdateUserRequest implements JsonRequestDto
{
    public function __construct(
        #[Assert\Length(max: 32)]
        public readonly ?string $name
    ) {
    }

    public static function fromArray(array $payload): static
    {
        $name = array_key_exists('name', $payload) ? (string) $payload['name'] : null;

        if ($name === null) {
            throw new \InvalidArgumentException('No updatable fields provided.');
        }

        return new self($name);
    }
}
