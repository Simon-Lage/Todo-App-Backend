<?php

declare(strict_types=1);

namespace App\Log\Service;

use App\Repository\LogRepository;
use DateTimeImmutable;

final class LogRetentionService
{
    private const MAX_SIZE_BYTES = 5368709120; // 5 GB
    private const BATCH_SIZE = 500;

    private ?DateTimeImmutable $lastRunAt = null;

    public function __construct(private readonly LogRepository $logRepository)
    {
    }

    public function enforce(): void
    {
        $size = $this->logRepository->getApproxSizeBytes();

        if ($size <= self::MAX_SIZE_BYTES) {
            return;
        }

        while ($size > self::MAX_SIZE_BYTES) {
            $deleted = $this->logRepository->deleteOldestBatch(self::BATCH_SIZE);
            if ($deleted === 0) {
                break;
            }
            $size = $this->logRepository->getApproxSizeBytes();
        }

        $this->lastRunAt = new DateTimeImmutable();
    }

    public function getLastRunAt(): ?DateTimeImmutable
    {
        return $this->lastRunAt;
    }
}
