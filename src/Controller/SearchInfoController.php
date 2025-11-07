<?php

declare(strict_types=1);

namespace App\Controller;

use App\Http\Response\ApiResponseFactory;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/info/search')]
final class SearchInfoController extends AbstractController
{
    public function __construct(private readonly ApiResponseFactory $responseFactory)
    {
    }

    #[Route('', name: 'api_info_search', methods: ['POST'])]
    public function info(): JsonResponse
    {
        return $this->responseFactory->single([
            'entity' => 'search',
            'action' => 'query',
            'fields' => [
                'entity' => ['type' => 'string', 'required' => false, 'nullable' => true, 'enum' => ['user', 'project', 'task', 'logs']],
                'q' => ['type' => 'string', 'required' => true, 'nullable' => false],
                'limit' => ['type' => 'integer', 'required' => false, 'nullable' => false, 'min' => 1, 'max' => 200],
                'filters' => ['type' => 'object', 'required' => false, 'nullable' => true],
            ],
            'examples' => [
                'task' => [
                    'entity' => 'task',
                    'q' => 'priority',
                    'filters' => [
                        'project_id' => '...uuid...',
                        'status' => 'in_progress',
                    ],
                ],
            ],
            'errors' => ['USED_ACCOUNT_IS_INACTIVE', 'PERMISSION_DENIED', 'VALIDATION_ERROR'],
        ]);
    }
}
