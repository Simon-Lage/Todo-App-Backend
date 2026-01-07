<?php

declare(strict_types=1);

namespace App\Ai\Service;

use App\Exception\ApiProblemException;
use JsonException;

final class GoogleStudioClient
{
    private const BASE_URL = 'https://generativelanguage.googleapis.com/v1beta';
    private const DEFAULT_MODEL = 'models/gemini-2.5-flash';

    public function __construct(private readonly string $apiKey, private readonly int $timeoutSeconds = 15)
    {
        if (trim($this->apiKey) === '') {
            throw ApiProblemException::internal('Google AI API key is not configured.');
        }
    }

    public function generateText(string $prompt, ?string $model = null): string
    {
        if (trim($prompt) === '') {
            throw ApiProblemException::internal('Prompt must not be empty.');
        }

        $response = $this->generateContent([
            [
                'parts' => [
                    ['text' => $prompt],
                ],
            ],
        ], $model);

        return $this->extractFirstText($response);
    }

    public function generateContent(array $contents, ?string $model = null): array
    {
        if ($contents === []) {
            throw ApiProblemException::internal('AI request payload is empty.');
        }

        return $this->request(['contents' => $contents], $model ?? self::DEFAULT_MODEL);
    }

    private function request(array $payload, string $model): array
    {
        try {
            $body = json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
        } catch (JsonException) {
            throw ApiProblemException::internal('Failed to encode AI request payload.');
        }

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => [
                    'Content-Type: application/json',
                    'x-goog-api-key: '.$this->apiKey,
                ],
                'content' => $body,
                'timeout' => $this->timeoutSeconds,
                'ignore_errors' => true,
            ],
        ]);

        $url = sprintf('%s/%s:generateContent', self::BASE_URL, $model);
        $errorMessage = null;
        set_error_handler(static function (int $severity, string $message) use (&$errorMessage): bool {
            $errorMessage = $message;
            return true;
        });
        $raw = file_get_contents($url, false, $context);
        restore_error_handler();
        $statusCode = $this->extractStatusCode($http_response_header ?? []);

        if ($raw === false) {
            throw ApiProblemException::internal($errorMessage ?? 'Failed to reach Google AI.');
        }

        try {
            $decoded = json_decode($raw, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw ApiProblemException::internal('Invalid response from Google AI.');
        }

        if ($statusCode >= 400 || $statusCode === 0) {
            $message = $decoded['error']['message'] ?? 'AI request failed.';
            throw ApiProblemException::internal($message);
        }

        if (!is_array($decoded)) {
            throw ApiProblemException::internal('Invalid response from Google AI.');
        }

        return $decoded;
    }

    private function extractStatusCode(array $headers): int
    {
        $status = 0;

        foreach ($headers as $header) {
            if (str_starts_with($header, 'HTTP/')) {
                $parts = explode(' ', $header, 3);
                if (isset($parts[1]) && is_numeric($parts[1])) {
                    $status = (int) $parts[1];
                }
            }
        }

        return $status;
    }

    private function extractFirstText(array $response): string
    {
        foreach ($response['candidates'] ?? [] as $candidate) {
            foreach ($candidate['content']['parts'] ?? [] as $part) {
                if (is_string($part['text'] ?? null) && trim($part['text']) !== '') {
                    return trim($part['text']);
                }
            }
        }

        throw ApiProblemException::internal('AI response did not include text.');
    }
}
