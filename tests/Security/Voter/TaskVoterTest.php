<?php

declare(strict_types=1);

namespace App\Tests\Security\Voter;

use App\Entity\Project;
use App\Entity\Task;
use App\Entity\User;
use App\Security\Permission\PermissionEnum;
use App\Security\Permission\PermissionRegistry;
use App\Security\Voter\TaskVoter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

final class TaskVoterTest extends TestCase
{
    private PermissionRegistry $permissionRegistry;
    private TaskVoter $voter;

    protected function setUp(): void
    {
        $this->permissionRegistry = $this->createMock(PermissionRegistry::class);
        $this->voter = new TaskVoter($this->permissionRegistry);
    }

    public function testSupportsTaskView(): void
    {
        $task = $this->createMock(Task::class);
        
        $reflection = new \ReflectionClass($this->voter);
        $method = $reflection->getMethod('supports');
        $method->setAccessible(true);
        
        $result = $method->invoke($this->voter, TaskVoter::VIEW, $task);
        
        $this->assertTrue($result);
    }

    public function testSupportsTaskCreate(): void
    {
        $reflection = new \ReflectionClass($this->voter);
        $method = $reflection->getMethod('supports');
        $method->setAccessible(true);
        
        $result = $method->invoke($this->voter, TaskVoter::CREATE, null);
        
        $this->assertTrue($result);
    }

    public function testDoesNotSupportUnknownAttribute(): void
    {
        $task = $this->createMock(Task::class);
        
        $reflection = new \ReflectionClass($this->voter);
        $method = $reflection->getMethod('supports');
        $method->setAccessible(true);
        
        $result = $method->invoke($this->voter, 'UNKNOWN_PERMISSION', $task);
        
        $this->assertFalse($result);
    }

    public function testUserCanCreateTaskWithPermission(): void
    {
        $user = $this->createUser();
        $token = $this->createTokenForUser($user);

        $this->permissionRegistry
            ->expects($this->once())
            ->method('has')
            ->with($user, PermissionEnum::CAN_CREATE_TASKS->value)
            ->willReturn(true);

        $result = $this->voter->vote($token, null, [TaskVoter::CREATE]);

        $this->assertEquals(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testUserCannotCreateTaskWithoutPermission(): void
    {
        $user = $this->createUser();
        $token = $this->createTokenForUser($user);

        $this->permissionRegistry
            ->expects($this->once())
            ->method('has')
            ->with($user, PermissionEnum::CAN_CREATE_TASKS->value)
            ->willReturn(false);

        $result = $this->voter->vote($token, null, [TaskVoter::CREATE]);

        $this->assertEquals(VoterInterface::ACCESS_DENIED, $result);
    }

    public function testOwnerCanViewTask(): void
    {
        $user = $this->createUser();
        $task = $this->createTaskWithOwner($user);
        $token = $this->createTokenForUser($user);

        $this->permissionRegistry
            ->expects($this->once())
            ->method('has')
            ->with($user, PermissionEnum::CAN_READ_ALL_TASKS->value)
            ->willReturn(false);

        $result = $this->voter->vote($token, $task, [TaskVoter::VIEW]);

        $this->assertEquals(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testAssignedUserCanViewTask(): void
    {
        $owner = $this->createUser();
        $assignee = $this->createUser();
        $task = $this->createTaskWithOwnerAndAssignee($owner, $assignee);
        $token = $this->createTokenForUser($assignee);

        $this->permissionRegistry
            ->expects($this->once())
            ->method('has')
            ->with($assignee, PermissionEnum::CAN_READ_ALL_TASKS->value)
            ->willReturn(false);

        $result = $this->voter->vote($token, $task, [TaskVoter::VIEW]);

        $this->assertEquals(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testUserWithReadAllPermissionCanViewAnyTask(): void
    {
        $owner = $this->createUser();
        $viewer = $this->createUser();
        $task = $this->createTaskWithOwner($owner);
        $token = $this->createTokenForUser($viewer);

        $this->permissionRegistry
            ->expects($this->once())
            ->method('has')
            ->with($viewer, PermissionEnum::CAN_READ_ALL_TASKS->value)
            ->willReturn(true);

        $result = $this->voter->vote($token, $task, [TaskVoter::VIEW]);

        $this->assertEquals(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testOwnerCanEditTask(): void
    {
        $user = $this->createUser();
        $task = $this->createTaskWithOwner($user);
        $token = $this->createTokenForUser($user);

        $this->permissionRegistry
            ->expects($this->once())
            ->method('has')
            ->with($user, PermissionEnum::CAN_EDIT_TASKS->value)
            ->willReturn(false);

        $result = $this->voter->vote($token, $task, [TaskVoter::EDIT]);

        $this->assertEquals(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testAssignedUserCanEditTask(): void
    {
        $owner = $this->createUser();
        $assignee = $this->createUser();
        $task = $this->createTaskWithOwnerAndAssignee($owner, $assignee);
        $token = $this->createTokenForUser($assignee);

        $this->permissionRegistry
            ->expects($this->once())
            ->method('has')
            ->with($assignee, PermissionEnum::CAN_EDIT_TASKS->value)
            ->willReturn(false);

        $result = $this->voter->vote($token, $task, [TaskVoter::EDIT]);

        $this->assertEquals(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testOnlyUsersWithPermissionCanDeleteTask(): void
    {
        $user = $this->createUser();
        $task = $this->createTaskWithOwner($user);
        $token = $this->createTokenForUser($user);

        $this->permissionRegistry
            ->expects($this->once())
            ->method('has')
            ->with($user, PermissionEnum::CAN_DELETE_TASKS->value)
            ->willReturn(true);

        $result = $this->voter->vote($token, $task, [TaskVoter::DELETE]);

        $this->assertEquals(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testOwnerCannotDeleteTaskWithoutPermission(): void
    {
        $user = $this->createUser();
        $task = $this->createTaskWithOwner($user);
        $token = $this->createTokenForUser($user);

        $this->permissionRegistry
            ->expects($this->once())
            ->method('has')
            ->with($user, PermissionEnum::CAN_DELETE_TASKS->value)
            ->willReturn(false);

        $result = $this->voter->vote($token, $task, [TaskVoter::DELETE]);

        $this->assertEquals(VoterInterface::ACCESS_DENIED, $result);
    }

    private function createUser(): User
    {
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn(new \Symfony\Component\Uid\Uuid('00000000-0000-0000-0000-000000000001'));
        return $user;
    }

    private function createTaskWithOwner(User $owner): Task
    {
        $task = $this->createMock(Task::class);
        $task->method('getCreatedByUser')->willReturn($owner);
        $task->method('isAssignedToUser')->willReturn(false);
        $task->method('getProject')->willReturn(null);
        return $task;
    }

    private function createTaskWithOwnerAndAssignee(User $owner, User $assignee): Task
    {
        $task = $this->createMock(Task::class);
        $task->method('getCreatedByUser')->willReturn($owner);
        $task->method('isAssignedToUser')->willReturnCallback(fn($user) => $user === $assignee);
        $task->method('getProject')->willReturn(null);
        return $task;
    }

    private function createTokenForUser(User $user): TokenInterface
    {
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);
        return $token;
    }
}

