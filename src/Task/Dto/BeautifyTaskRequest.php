<?php

declare(strict_types=1);

namespace App\Task\Dto;

use App\Http\Request\JsonRequestDto;
use Symfony\Component\Validator\Constraints as Assert;

final class BeautifyTaskRequest implements JsonRequestDto
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(max: 65535)]
        public readonly string $description,
        #[Assert\Length(max: 255)]
        public readonly ?string $title
    ) {
    }

    public static function fromArray(array $payload): static
    {
        if (!array_key_exists('description', $payload)) {
            throw new \InvalidArgumentException('Description is required.');
        }

        $title = array_key_exists('title', $payload) ? ($payload['title'] === null ? null : (string) $payload['title']) : null;

        return new self(
            (string) $payload['description'],
            $title
        );
    }
}
