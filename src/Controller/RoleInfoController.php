<?php

declare(strict_types=1);

namespace App\Controller;

use App\Http\Response\ApiResponseFactory;
use App\Security\Permission\PermissionRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/info/role')]
final class RoleInfoController extends AbstractController
{
    public function __construct(private readonly ApiResponseFactory $responseFactory, private readonly PermissionRegistry $permissionRegistry)
    {
    }

    #[Route('', name: 'api_info_role_create', methods: ['POST'])]
    public function create(): JsonResponse
    {
        return $this->responseFactory->single([
            'entity' => 'role',
            'action' => 'create',
            'fields' => $this->permissionFields(required: false, default: false),
        ]);
    }

    #[Route('/update', name: 'api_info_role_update', methods: ['POST'])]
    public function update(): JsonResponse
    {
        return $this->responseFactory->single([
            'entity' => 'role',
            'action' => 'update',
            'fields' => $this->permissionFields(required: false, default: null),
        ]);
    }

    #[Route('/assign', name: 'api_info_role_assign', methods: ['POST'])]
    public function assign(): JsonResponse
    {
        return $this->responseFactory->single([
            'entity' => 'role_assignment',
            'action' => 'replace',
            'fields' => [
                'roles' => ['type' => 'array', 'required' => true, 'nullable' => false, 'items' => ['type' => 'uuid']],
            ],
        ]);
    }

    private function permissionFields(bool $required, ?bool $default): array
    {
        $fields = [];
        foreach ($this->permissionRegistry->catalog() as $permission) {
            $fields[$permission] = [
                'type' => 'boolean',
                'required' => $required,
                'nullable' => false,
            ];
            if ($default !== null) {
                $fields[$permission]['default'] = $default;
            }
        }

        return $fields;
    }
}
