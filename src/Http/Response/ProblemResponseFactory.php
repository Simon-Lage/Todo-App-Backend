<?php

declare(strict_types=1);

namespace App\Http\Response;

use App\Exception\ApiProblemException;
use Symfony\Component\HttpFoundation\JsonResponse;

final class ProblemResponseFactory
{
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

        return new JsonResponse($payload, $exception->getStatusCode(), $exception->getHeaders());
    }
}
