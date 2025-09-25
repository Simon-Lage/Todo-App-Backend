<?php

declare(strict_types=1);

namespace App\Task\Dto;

use App\Http\Request\JsonRequestDto;
use Symfony\Component\Validator\Constraints as Assert;

final class MoveTaskRequest implements JsonRequestDto
{
    public function __construct(
        public readonly ?string $projectId
    ) {
    }

    public static function fromArray(array $payload): static
    {
        $projectId = array_key_exists('project_id', $payload) ? ($payload['project_id'] === null ? null : (string) $payload['project_id']) : null;

        return new self($projectId);
    }
}
