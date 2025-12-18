<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Log;
use App\Exception\ApiProblemException;
use App\Http\Response\ApiResponseFactory;
use App\Log\Service\LogQueryService;
use App\Log\Service\LogRetentionService;
use App\Log\View\LogViewFactory;
use App\Repository\LogRepository;
use App\Security\Permission\PermissionEnum;
use DateTimeImmutable;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Uid\Uuid;

#[Route('/api/logs')]
final class LogController extends AbstractController
{
    public function __construct(
        private readonly ApiResponseFactory $responseFactory,
        private readonly LogQueryService $logQueryService,
        private readonly LogRetentionService $logRetentionService,
        private readonly LogRepository $logRepository,
        private readonly LogViewFactory $logViewFactory
    ) {
    }

    #[Route('/list', name: 'api_logs_list', methods: ['GET'])]
    #[IsGranted('perm:'.PermissionEnum::CAN_READ_LOGS->value)]
    public function list(Request $request): JsonResponse
    {
        [$offset, $limit, $sortBy, $direction] = $this->resolvePagination($request, ['performed_at', 'action']);

        $filters = [
            'action' => $this->nullableString($request->query->get('action')),
            'performed_by_user_id' => $this->optionalUuid($request->query->get('performed_by_user_id'), 'performed_by_user_id'),
            'from' => $this->optionalDate($request->query->get('from'), 'from'),
            'to' => $this->optionalDate($request->query->get('to'), 'to'),
            'q' => $this->nullableString($request->query->get('q')),
        ];

        $result = $this->logQueryService->list($filters, $offset, $limit, $sortBy, $direction);
        $items = array_map(fn(Log $log) => $this->logViewFactory->make($log), $result['items']);

        return $this->responseFactory->collection($items, $result['total'], $offset, $limit, $sortBy, strtoupper($direction));
    }

    #[Route('/{id}', name: 'api_logs_show', methods: ['GET'])]
    #[IsGranted('perm:'.PermissionEnum::CAN_READ_LOGS->value)]
    public function show(string $id): JsonResponse
    {
        $log = $this->findLog($id);

        return $this->responseFactory->single($this->logViewFactory->make($log));
    }

    #[Route('/stats', name: 'api_logs_stats', methods: ['GET'])]
    #[IsGranted('perm:'.PermissionEnum::CAN_READ_LOGS->value)]
    public function stats(): JsonResponse
    {
        $stats = $this->logQueryService->stats();
        $lastRetention = $this->logRetentionService->getLastRunAt()?->format(DATE_ATOM);

        return $this->responseFactory->single($stats + ['last_retention_run_at' => $lastRetention]);
    }

    private function findLog(string $id): Log
    {
        $uuid = $this->toUuid($id, 'id');
        $log = $this->logRepository->find($uuid);

        if (!$log instanceof Log) {
            throw ApiProblemException::notFound('Log entry not found.');
        }

        return $log;
    }

    private function resolvePagination(Request $request, array $allowedSorts): array
    {
        $offset = max(0, (int) $request->query->get('offset', 0));
        $limit = (int) $request->query->get('limit', 100);
        if ($limit < 1) {
            throw ApiProblemException::validation(['limit' => ['Limit must be at least 1.']]);
        }
        $limit = min(500, $limit);

        $sortBy = (string) $request->query->get('sort_by', $allowedSorts[0] ?? 'performed_at');
        if (!in_array($sortBy, $allowedSorts, true)) {
            $sortBy = $allowedSorts[0] ?? 'performed_at';
        }

        $direction = strtolower((string) $request->query->get('direction', 'desc')) === 'asc' ? 'asc' : 'desc';

        return [$offset, $limit, $sortBy, $direction];
    }

    private function optionalUuid(?string $value, string $field): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return Uuid::fromString($value)->toRfc4122();
        } catch (\InvalidArgumentException) {
            throw ApiProblemException::validation([$field => ['Invalid UUID.']]);
        }
    }

    private function optionalDate(?string $value, string $field): ?DateTimeImmutable
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return new DateTimeImmutable($value);
        } catch (\Exception) {
            throw ApiProblemException::validation([$field => ['Invalid date format.']]);
        }
    }

    private function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim((string) $value);

        return $trimmed === '' ? null : $trimmed;
    }

    private function toUuid(string $value, string $field): Uuid
    {
        try {
            return Uuid::fromString($value);
        } catch (\InvalidArgumentException) {
            throw ApiProblemException::validation([$field => ['Invalid UUID.']]);
        }
    }
}
