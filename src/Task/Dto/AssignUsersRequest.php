<?php

declare(strict_types=1);

namespace App\Task\Dto;

use App\Http\Request\JsonRequestDto;
use Symfony\Component\Validator\Constraints as Assert;

final class AssignUsersRequest implements JsonRequestDto
{
    /**
     * @param string[] $userIds
     */
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Type('array')]
        public readonly array $userIds
    ) {
    }

    public static function fromArray(array $payload): static
    {
        if (!array_key_exists('user_ids', $payload) || !is_array($payload['user_ids'])) {
            throw new \InvalidArgumentException('user_ids array is required.');
        }

        return new self(array_map('strval', $payload['user_ids']));
    }
}

