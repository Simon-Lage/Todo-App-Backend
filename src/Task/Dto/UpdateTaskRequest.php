<?php

declare(strict_types=1);

namespace App\Task\Dto;

use App\Http\Request\JsonRequestDto;
use Symfony\Component\Validator\Constraints as Assert;

final class UpdateTaskRequest implements JsonRequestDto
{
    public function __construct(
        #[Assert\Length(max: 255)]
        public readonly ?string $title,
        #[Assert\Length(max: 65535)]
        public readonly ?string $description,
        #[Assert\Length(max: 50)]
        public readonly ?string $priority,
        #[Assert\AtLeastOneOf([
            new Assert\Date(),
            new Assert\DateTime(),
        ])]
        public readonly ?string $dueDate,
        public readonly bool $clearDueDate
    ) {
    }

    public static function fromArray(array $payload): static
    {
        $title = array_key_exists('title', $payload) ? ($payload['title'] === null ? null : (string) $payload['title']) : null;
        $description = array_key_exists('description', $payload) ? ($payload['description'] === null ? null : (string) $payload['description']) : null;
        $priority = array_key_exists('priority', $payload) ? ($payload['priority'] === null ? null : (string) $payload['priority']) : null;
        $dueDateProvided = array_key_exists('due_date', $payload);
        $dueDate = $dueDateProvided ? ($payload['due_date'] === null ? null : (string) $payload['due_date']) : null;

        if ($title === null && $description === null && $priority === null && !$dueDateProvided) {
            throw new \InvalidArgumentException('No updatable fields provided.');
        }

        return new self($title, $description, $priority, $dueDate, $dueDateProvided && $payload['due_date'] === null);
    }
}
