<?php

declare(strict_types=1);

namespace App\Controller;

use App\Http\Response\ApiResponseFactory;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/info/task')]
final class TaskInfoController extends AbstractController
{
    public function __construct(private readonly ApiResponseFactory $responseFactory)
    {
    }

    #[Route('', name: 'api_info_task_create', methods: ['POST'])]
    public function create(): JsonResponse
    {
        return $this->responseFactory->single([
            'entity' => 'task',
            'action' => 'create',
            'fields' => [
                'title' => ['type' => 'string', 'required' => true, 'nullable' => false, 'maxLength' => 255],
                'description' => ['type' => 'string', 'required' => false, 'nullable' => true, 'maxLength' => 65535],
                'status' => ['type' => 'string', 'required' => true, 'nullable' => false, 'maxLength' => 50],
                'priority' => ['type' => 'string', 'required' => true, 'nullable' => false, 'maxLength' => 50],
                'due_date' => ['type' => 'datetime', 'required' => false, 'nullable' => true, 'format' => 'ISO-8601'],
                'assigned_to_user_id' => ['type' => 'uuid', 'required' => false, 'nullable' => true],
                'project_id' => ['type' => 'uuid', 'required' => false, 'nullable' => true],
            ],
            'errors' => ['USED_ACCOUNT_IS_INACTIVE', 'VALIDATION_ERROR', 'PERMISSION_DENIED', 'RESOURCE_NOT_FOUND'],
        ]);
    }

    #[Route('/update', name: 'api_info_task_update', methods: ['POST'])]
    public function update(): JsonResponse
    {
        return $this->responseFactory->single([
            'entity' => 'task',
            'action' => 'update',
            'fields' => [
                'title' => ['type' => 'string', 'required' => false, 'nullable' => false, 'maxLength' => 255],
                'description' => ['type' => 'string', 'required' => false, 'nullable' => true, 'maxLength' => 65535],
                'priority' => ['type' => 'string', 'required' => false, 'nullable' => false, 'maxLength' => 50],
                'due_date' => ['type' => 'datetime', 'required' => false, 'nullable' => true, 'format' => 'ISO-8601'],
            ],
            'errors' => ['USED_ACCOUNT_IS_INACTIVE', 'VALIDATION_ERROR', 'PERMISSION_DENIED', 'RESOURCE_NOT_FOUND'],
        ]);
    }

    #[Route('/assign-user', name: 'api_info_task_assign_user', methods: ['POST'])]
    public function assignUser(): JsonResponse
    {
        return $this->responseFactory->single([
            'entity' => 'task_assignment',
            'action' => 'assign',
            'fields' => [
                'user_id' => ['type' => 'uuid', 'required' => true, 'nullable' => false],
            ],
            'errors' => ['USED_ACCOUNT_IS_INACTIVE', 'VALIDATION_ERROR', 'PERMISSION_DENIED', 'RESOURCE_NOT_FOUND'],
        ]);
    }

    #[Route('/unassign-user', name: 'api_info_task_unassign_user', methods: ['POST'])]
    public function unassignUser(): JsonResponse
    {
        return $this->responseFactory->single([
            'entity' => 'task_assignment',
            'action' => 'unassign',
            'fields' => [],
            'errors' => ['USED_ACCOUNT_IS_INACTIVE', 'PERMISSION_DENIED', 'RESOURCE_NOT_FOUND'],
        ]);
    }

    #[Route('/move-to-project', name: 'api_info_task_move_to_project', methods: ['POST'])]
    public function moveToProject(): JsonResponse
    {
        return $this->responseFactory->single([
            'entity' => 'task_project_assignment',
            'action' => 'move',
            'fields' => [
                'project_id' => ['type' => 'uuid', 'required' => false, 'nullable' => true],
            ],
            'errors' => ['USED_ACCOUNT_IS_INACTIVE', 'VALIDATION_ERROR', 'PERMISSION_DENIED', 'RESOURCE_NOT_FOUND'],
        ]);
    }

    #[Route('/status', name: 'api_info_task_update_status', methods: ['POST'])]
    public function updateStatus(): JsonResponse
    {
        return $this->responseFactory->single([
            'entity' => 'task_status',
            'action' => 'update',
            'fields' => [
                'status' => ['type' => 'string', 'required' => true, 'nullable' => false, 'maxLength' => 50],
            ],
            'errors' => ['USED_ACCOUNT_IS_INACTIVE', 'VALIDATION_ERROR', 'PERMISSION_DENIED', 'RESOURCE_NOT_FOUND'],
        ]);
    }
}
