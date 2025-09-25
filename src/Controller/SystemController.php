<?php

declare(strict_types=1);

namespace App\Controller;

use App\Http\Response\ApiResponseFactory;
use DateTimeImmutable;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api')]
final class SystemController extends AbstractController
{
    public function __construct(private readonly ApiResponseFactory $responseFactory, private readonly string $appVersion)
    {
    }

    #[Route('/health', name: 'api_health', methods: ['GET'])]
    public function health(): JsonResponse
    {
        return $this->responseFactory->single([
            'status' => 'ok',
            'checked_at' => (new DateTimeImmutable())->format(DATE_ATOM),
        ]);
    }

    #[Route('/version', name: 'api_version', methods: ['GET'])]
    public function version(): JsonResponse
    {
        return $this->responseFactory->single([
            'version' => $this->appVersion,
        ]);
    }
}
