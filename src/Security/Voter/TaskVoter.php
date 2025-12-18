<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Entity\Project;
use App\Entity\Task;
use App\Entity\User;
use App\Security\Permission\PermissionEnum;
use App\Security\Permission\PermissionRegistry;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

final class TaskVoter extends Voter
{
    public const VIEW = 'TASK_VIEW';
    public const EDIT = 'TASK_EDIT';
    public const DELETE = 'TASK_DELETE';
    public const CREATE = 'TASK_CREATE';
    public const STATUS = 'TASK_STATUS';

    public function __construct(
        private readonly PermissionRegistry $permissionRegistry
    ) {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        if ($attribute === self::CREATE) {
            return true;
        }

        return in_array($attribute, [self::VIEW, self::EDIT, self::DELETE, self::STATUS])
            && $subject instanceof Task;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        if ($attribute === self::CREATE) {
            return $this->permissionRegistry->has($user, PermissionEnum::CAN_CREATE_TASKS->value);
        }

        /** @var Task $task */
        $task = $subject;

        return match ($attribute) {
            self::VIEW => $this->canView($task, $user),
            self::EDIT => $this->canEdit($task, $user),
            self::STATUS => $this->canChangeStatus($task, $user),
            self::DELETE => $this->canDelete($task, $user),
            default => false,
        };
    }

    private function canView(Task $task, User $user): bool
    {
        if ($this->isOwner($task, $user)) {
            return true;
        }

        if ($this->isAssignee($task, $user)) {
            return true;
        }

        $project = $task->getProject();
        if ($project instanceof Project) {
            $projectOwner = $project->getCreatedByUser();
            if ($projectOwner !== null && $projectOwner->getId()?->equals($user->getId())) {
                return true;
            }

            if ($this->permissionRegistry->has($user, PermissionEnum::CAN_READ_ALL_TASKS->value) && $project->isTeamLead($user)) {
                return true;
            }
        }

        return false;
    }

    private function canEdit(Task $task, User $user): bool
    {
        if ($this->isOwner($task, $user)) {
            return true;
        }

        if ($this->isAssignee($task, $user)) {
            return true;
        }

        if ($this->permissionRegistry->has($user, PermissionEnum::CAN_EDIT_TASKS->value)) {
            $project = $task->getProject();
            if ($project instanceof Project) {
                return $project->isTeamLead($user);
            }

            return $this->isOwner($task, $user);
        }

        return false;
    }

    private function canChangeStatus(Task $task, User $user): bool
    {
        return $this->canEdit($task, $user);
    }

    private function canDelete(Task $task, User $user): bool
    {
        if ($this->permissionRegistry->has($user, PermissionEnum::CAN_DELETE_TASKS->value)) {
            return true;
        }
        
        return false;
    }

    private function isOwner(Task $task, User $user): bool
    {
        return $task->getCreatedByUser()?->getId()?->equals($user->getId()) ?? false;
    }

    private function isAssignee(Task $task, User $user): bool
    {
        return $task->isAssignedToUser($user);
    }
}

