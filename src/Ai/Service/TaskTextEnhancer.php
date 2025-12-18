<?php

declare(strict_types=1);

namespace App\Ai\Service;

use App\Exception\ApiProblemException;

final class TaskTextEnhancer
{
    private const BASE_PROMPT = 'Improve this content formatting and rephrase it, to better fit a task-text for a TODO App. Only return the improved task-text, never return anything else, this text will be straightly output to the user. If the content cannot be improved, due to being nonsense or too short, you just return "CONTENT_NOT_PROCESSABLE". Always keep the language inside the content for your answer';
    private const NOT_PROCESSABLE = 'CONTENT_NOT_PROCESSABLE';

    public function __construct(private readonly GoogleStudioClient $client, private readonly bool $debug)
    {
    }

    public function improve(string $description, ?string $title = null): string
    {
        $prompt = $this->buildPrompt($description, $title);

        try {
            $result = $this->client->generateText($prompt);
            $trimmed = trim($result);

            if (strcasecmp($trimmed, self::NOT_PROCESSABLE) === 0) {
                throw ApiProblemException::fromStatus(422, 'Unprocessable Entity', self::NOT_PROCESSABLE, self::NOT_PROCESSABLE);
            }

            return $trimmed;
        } catch (ApiProblemException $exception) {
            if ($exception->getProblemCode() === self::NOT_PROCESSABLE) {
                throw $exception;
            }

            $message = $this->debug ? $exception->getDetail() : 'AI request failed.';
            throw ApiProblemException::fromStatus(502, 'Bad Gateway', $message, 'AI_REQUEST_FAILED');
        } catch (\Throwable $exception) {
            $message = $this->debug ? $exception->getMessage() : 'AI request failed.';
            throw ApiProblemException::fromStatus(502, 'Bad Gateway', $message, 'AI_REQUEST_FAILED');
        }
    }

    private function buildPrompt(string $description, ?string $title): string
    {
        $contextLines = [];

        if ($title !== null && trim($title) !== '') {
            $contextLines[] = 'Title: '.trim($title);
        }

        $contextLines[] = 'Description: '.trim($description);

        $content = '<content>'.implode(PHP_EOL, $contextLines).'</content>';

        return self::BASE_PROMPT.PHP_EOL.$content;
    }
}
