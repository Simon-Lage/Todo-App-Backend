<?php

declare(strict_types=1);

namespace App\Controller;

use App\Http\Response\ApiResponseFactory;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/info/project')]
final class ProjectInfoController extends AbstractController
{
    public function __construct(private readonly ApiResponseFactory $responseFactory)
    {
    }

    #[Route('', name: 'api_info_project_create', methods: ['POST'])]
    public function create(): JsonResponse
    {
        return $this->responseFactory->single([
            'entity' => 'project',
            'action' => 'create',
            'fields' => [
                'name' => ['type' => 'string', 'required' => true, 'nullable' => false, 'maxLength' => 255, 'unique' => true],
                'description' => ['type' => 'string', 'required' => false, 'nullable' => true, 'maxLength' => 65535],
            ],
            'errors' => ['USED_ACCOUNT_IS_INACTIVE', 'VALIDATION_ERROR', 'CONFLICT'],
        ]);
    }

    #[Route('/update', name: 'api_info_project_update', methods: ['POST'])]
    public function update(): JsonResponse
    {
        return $this->responseFactory->single([
            'entity' => 'project',
            'action' => 'update',
            'fields' => [
                'name' => ['type' => 'string', 'required' => false, 'nullable' => false, 'maxLength' => 255, 'unique' => true],
                'description' => ['type' => 'string', 'required' => false, 'nullable' => true, 'maxLength' => 65535],
            ],
            'errors' => ['USED_ACCOUNT_IS_INACTIVE', 'VALIDATION_ERROR', 'CONFLICT', 'RESOURCE_NOT_FOUND'],
        ]);
    }
}
