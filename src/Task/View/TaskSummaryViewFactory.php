<?php

declare(strict_types=1);

namespace App\Task\View;

use App\Entity\Task;

final class TaskSummaryViewFactory
{
    public function make(Task $task): array
    {
        $assignedUserIds = [];
        foreach ($task->getAssignedUsers() as $user) {
            $assignedUserIds[] = $user->getId()?->toRfc4122();
        }

        return [
            'id' => $task->getId()?->toRfc4122(),
            'title' => $task->getTitle(),
            'status' => $task->getStatus(),
            'priority' => $task->getPriority(),
            'due_date' => $task->getDueDate()?->format(DATE_ATOM),
            'created_at' => $task->getCreatedAt()?->format(DATE_ATOM),
            'updated_at' => $task->getUpdatedAt()?->format(DATE_ATOM),
            'project_id' => $task->getProject()?->getId()?->toRfc4122(),
            'assigned_user_ids' => $assignedUserIds,
        ];
    }
}
