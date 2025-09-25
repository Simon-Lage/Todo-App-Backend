<?php

declare(strict_types=1);

namespace App\Task\View;

use App\Entity\Task;

final class TaskViewFactory
{
    public function make(Task $task): array
    {
        return [
            'id' => $task->getId()?->toRfc4122(),
            'title' => $task->getTitle(),
            'description' => $task->getDescription(),
            'status' => $task->getStatus(),
            'priority' => $task->getPriority(),
            'due_date' => $task->getDueDate()?->format(DATE_ATOM),
            'created_by_user_id' => $task->getCreatedByUser()?->getId()?->toRfc4122(),
            'assigned_to_user_id' => $task->getAssignedToUser()?->getId()?->toRfc4122(),
            'project_id' => $task->getProject()?->getId()?->toRfc4122(),
            'created_at' => $task->getCreatedAt()?->format(DATE_ATOM),
            'updated_at' => $task->getUpdatedAt()?->format(DATE_ATOM),
        ];
    }
}
