<?php

declare(strict_types=1);

namespace App\Controller;

use App\Http\Response\ApiResponseFactory;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/info/image')]
final class ImageInfoController extends AbstractController
{
    public function __construct(private readonly ApiResponseFactory $responseFactory)
    {
    }

    #[Route('', name: 'api_info_image_create', methods: ['POST'])]
    public function create(): JsonResponse
    {
        return $this->responseFactory->single([
            'entity' => 'image',
            'action' => 'create',
            'fields' => [
                'file' => ['type' => 'file', 'required' => true, 'nullable' => false],
                'type' => ['type' => 'string', 'required' => true, 'nullable' => false, 'maxLength' => 50],
                'user_id' => ['type' => 'uuid', 'required' => false, 'nullable' => true],
                'project_id' => ['type' => 'uuid', 'required' => false, 'nullable' => true],
                'task_id' => ['type' => 'uuid', 'required' => false, 'nullable' => true],
            ],
            'relations' => [
                'exactly_one_of' => ['user_id', 'project_id', 'task_id'],
            ],
        ]);
    }

    #[Route('/update', name: 'api_info_image_update', methods: ['POST'])]
    public function update(): JsonResponse
    {
        return $this->responseFactory->single([
            'entity' => 'image',
            'action' => 'update',
            'fields' => [
                'type' => ['type' => 'string', 'required' => false, 'nullable' => false, 'maxLength' => 50],
                'user_id' => ['type' => 'uuid', 'required' => false, 'nullable' => true],
                'project_id' => ['type' => 'uuid', 'required' => false, 'nullable' => true],
                'task_id' => ['type' => 'uuid', 'required' => false, 'nullable' => true],
            ],
            'relations' => [
                'max_one_of' => ['user_id', 'project_id', 'task_id'],
            ],
        ]);
    }
}
