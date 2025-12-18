<?php

declare(strict_types=1);

namespace App\Task\View;

use App\Entity\Task;
use App\Entity\User;

final class TaskViewFactory
{
    public function make(Task $task): array
    {
        $assignedUserIds = [];
        $assignedUsers = [];
        foreach ($task->getAssignedUsers() as $user) {
            if (!$user instanceof User) {
                continue;
            }

            $userId = $user->getId()?->toRfc4122();
            if ($userId === null) {
                continue;
            }

            $assignedUserIds[] = $userId;
            $assignedUsers[] = [
                'id' => $userId,
                'name' => $user->getName(),
                'profile_image_id' => $user->getProfileImage()?->getId()?->toRfc4122(),
            ];
        }

        return [
            'id' => $task->getId()?->toRfc4122(),
            'title' => $task->getTitle(),
            'description' => $task->getDescription(),
            'status' => $task->getStatus(),
            'priority' => $task->getPriority(),
            'due_date' => $task->getDueDate()?->format(DATE_ATOM),
            'created_by_user_id' => $task->getCreatedByUser()?->getId()?->toRfc4122(),
            'reviewer_user_id' => $task->getReviewerUser()?->getId()?->toRfc4122(),
            'finalized_by_user_id' => $task->getFinalizedByUser()?->getId()?->toRfc4122(),
            'finalized_at' => $task->getFinalizedAt()?->format(DATE_ATOM),
            'assigned_user_ids' => $assignedUserIds,
            'assigned_users' => $assignedUsers,
            'project_id' => $task->getProject()?->getId()?->toRfc4122(),
            'created_at' => $task->getCreatedAt()?->format(DATE_ATOM),
            'updated_at' => $task->getUpdatedAt()?->format(DATE_ATOM),
        ];
    }
}
