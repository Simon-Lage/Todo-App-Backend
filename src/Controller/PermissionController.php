<?php

declare(strict_types=1);

namespace App\Controller;

use App\Http\Response\ApiResponseFactory;
use App\Security\Permission\PermissionRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/permission')]
final class PermissionController extends AbstractController
{
    public function __construct(private readonly ApiResponseFactory $responseFactory, private readonly PermissionRegistry $permissionRegistry)
    {
    }

    #[Route('/catalog', name: 'api_permission_catalog', methods: ['GET'])]
    #[IsGranted('perm:perm_can_edit_user')]
    public function catalog(): JsonResponse
    {
        return $this->responseFactory->single(['items' => $this->permissionRegistry->catalog()]);
    }
}
