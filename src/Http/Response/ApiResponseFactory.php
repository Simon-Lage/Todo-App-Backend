<?php

declare(strict_types=1);

namespace App\Http\Response;

use Symfony\Component\HttpFoundation\JsonResponse;

final class ApiResponseFactory
{
    public function single(array $data, array $meta = [], int $status = 200, array $headers = []): JsonResponse
    {
        $payload = ['data' => $data];

        if ($meta !== []) {
            $payload += $meta;
        }

        return new JsonResponse($payload, $status, $headers);
    }

    public function collection(array $items, int $total, int $offset, int $limit, string $sortBy, string $direction, array $meta = [], array $headers = []): JsonResponse
    {
        $payload = [
            'items' => $items,
            'total' => $total,
            'offset' => $offset,
            'limit' => $limit,
            'sort_by' => $sortBy,
            'direction' => $direction,
        ];

        if ($meta !== []) {
            $payload += $meta;
        }

        return new JsonResponse($payload, 200, $headers);
    }
}
