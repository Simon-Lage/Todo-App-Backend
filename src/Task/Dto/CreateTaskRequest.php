<?php

declare(strict_types=1);

namespace App\Task\Dto;

use App\Http\Request\JsonRequestDto;
use Symfony\Component\Validator\Constraints as Assert;

final class CreateTaskRequest implements JsonRequestDto
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(max: 255)]
        public readonly string $title,
        #[Assert\Length(max: 65535)]
        public readonly ?string $description,
        #[Assert\NotBlank]
        #[Assert\Length(max: 50)]
        public readonly string $status,
        #[Assert\NotBlank]
        #[Assert\Length(max: 50)]
        public readonly string $priority,
        #[Assert\DateTime]
        public readonly ?string $dueDate,
        public readonly ?string $assignedToUserId,
        public readonly ?string $projectId
    ) {
    }

    public static function fromArray(array $payload): static
    {
        if (!array_key_exists('title', $payload)) {
            throw new \InvalidArgumentException('Title is required.');
        }

        if (!array_key_exists('status', $payload)) {
            throw new \InvalidArgumentException('Status is required.');
        }

        if (!array_key_exists('priority', $payload)) {
            throw new \InvalidArgumentException('Priority is required.');
        }

        $description = array_key_exists('description', $payload) ? (string) $payload['description'] : null;
        $dueDate = array_key_exists('due_date', $payload) ? ($payload['due_date'] === null ? null : (string) $payload['due_date']) : null;
        $assigned = array_key_exists('assigned_to_user_id', $payload) ? ($payload['assigned_to_user_id'] === null ? null : (string) $payload['assigned_to_user_id']) : null;
        $project = array_key_exists('project_id', $payload) ? ($payload['project_id'] === null ? null : (string) $payload['project_id']) : null;

        return new self(
            (string) $payload['title'],
            $description,
            (string) $payload['status'],
            (string) $payload['priority'],
            $dueDate,
            $assigned,
            $project
        );
    }
}
