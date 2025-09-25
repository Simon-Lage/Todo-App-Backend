<?php

declare(strict_types=1);

namespace App\Exception;

use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

final class ApiProblemException extends \RuntimeException implements HttpExceptionInterface
{
    private array $headers;

    public function __construct(
        private readonly int $statusCode,
        private readonly string $title,
        private readonly string $detail,
        private readonly string $problemCode,
        private readonly array $errors = [],
        array $headers = []
    ) {
        parent::__construct($detail);
        $this->headers = $headers;
    }

    public static function fromStatus(int $statusCode, string $title, string $detail, string $problemCode, array $errors = [], array $headers = []): self
    {
        return new self($statusCode, $title, $detail, $problemCode, $errors, $headers);
    }

    public static function validation(array $errors): self
    {
        return new self(422, 'Unprocessable Entity', 'Validation failed.', 'VALIDATION_ERROR', $errors);
    }

    public static function unauthorized(string $detail = 'Authentication is required.'): self
    {
        return new self(401, 'Unauthorized', $detail, 'TOKEN_INVALID');
    }

    public static function forbidden(string $detail = 'You do not have permission to perform this action.'): self
    {
        return new self(403, 'Forbidden', $detail, 'PERMISSION_DENIED');
    }

    public static function notFound(string $detail = 'Resource not found.'): self
    {
        return new self(404, 'Not Found', $detail, 'RESOURCE_NOT_FOUND');
    }

    public static function conflict(string $detail): self
    {
        return new self(409, 'Conflict', $detail, 'CONFLICT');
    }

    public static function payloadTooLarge(string $detail): self
    {
        return new self(413, 'Payload Too Large', $detail, 'PAYLOAD_TOO_LARGE');
    }

    public static function internal(string $detail = 'An unexpected error occurred.'): self
    {
        return new self(500, 'Internal Server Error', $detail, 'INTERNAL_ERROR');
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getDetail(): string
    {
        return $this->detail;
    }

    public function getProblemCode(): string
    {
        return $this->problemCode;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
