<?php

declare(strict_types=1);

namespace App\Http\Response;

use App\Exception\ApiProblemException;
use Symfony\Component\HttpFoundation\JsonResponse;

final class ProblemResponseFactory
{
    public function __construct(private readonly bool $debug)
    {
    }

    public function create(ApiProblemException $exception): JsonResponse
    {
        $payload = [
            'type' => 'about:blank',
            'title' => $exception->getTitle(),
            'status' => $exception->getStatusCode(),
            'detail' => $exception->getDetail(),
            'code' => $exception->getProblemCode(),
        ];

        if ($exception->getErrors() !== []) {
            $payload['errors'] = $exception->getErrors();
        }

        if ($this->debug) {
            $payload['debug'] = [
                'exception_class' => get_class($exception),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => array_slice($exception->getTrace(), 0, 5),
            ];
        }

        return new JsonResponse($payload, $exception->getStatusCode(), $exception->getHeaders());
    }
}
