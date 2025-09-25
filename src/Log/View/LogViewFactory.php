<?php

declare(strict_types=1);

namespace App\Log\View;

use App\Entity\Log;

final class LogViewFactory
{
    public function make(Log $log): array
    {
        return [
            'id' => $log->getId()?->toRfc4122(),
            'action' => $log->getAction(),
            'performed_by_user_id' => $log->getPerformedByUser()?->getId()?->toRfc4122(),
            'performed_at' => $log->getPerformedAt()?->format(DATE_ATOM),
            'details' => $log->getDetailsDecoded(),
        ];
    }
}
