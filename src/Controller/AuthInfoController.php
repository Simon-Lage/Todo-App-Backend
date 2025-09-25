<?php

declare(strict_types=1);

namespace App\Controller;

use App\Http\Response\ApiResponseFactory;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/info/auth')]
final class AuthInfoController extends AbstractController
{
    public function __construct(private readonly ApiResponseFactory $responseFactory)
    {
    }

    #[Route('/login', name: 'api_info_auth_login', methods: ['POST'])]
    public function login(): JsonResponse
    {
        return $this->responseFactory->single([
            'entity' => 'auth_login',
            'action' => 'create',
            'fields' => [
                'email' => ['type' => 'string', 'required' => true, 'nullable' => false, 'format' => 'email'],
                'password' => ['type' => 'string', 'required' => true, 'nullable' => false],
            ],
        ]);
    }

    #[Route('/refresh', name: 'api_info_auth_refresh', methods: ['POST'])]
    public function refresh(): JsonResponse
    {
        return $this->responseFactory->single([
            'entity' => 'auth_refresh',
            'action' => 'refresh',
            'fields' => [
                'refresh_token' => ['type' => 'string', 'required' => true, 'nullable' => false],
            ],
        ]);
    }

    #[Route('/logout', name: 'api_info_auth_logout', methods: ['POST'])]
    public function logout(): JsonResponse
    {
        return $this->responseFactory->single([
            'entity' => 'auth_logout',
            'action' => 'invalidate',
            'fields' => [
                'refresh_token' => ['type' => 'string', 'required' => true, 'nullable' => false],
            ],
        ]);
    }

    #[Route('/change-password', name: 'api_info_auth_change_password', methods: ['POST'])]
    public function changePassword(): JsonResponse
    {
        return $this->responseFactory->single([
            'entity' => 'auth_change_password',
            'action' => 'update',
            'fields' => [
                'current_password' => ['type' => 'string', 'required' => true, 'nullable' => false],
                'new_password' => ['type' => 'string', 'required' => true, 'nullable' => false, 'minLength' => 12],
            ],
        ]);
    }

    #[Route('/reset-password/confirm', name: 'api_info_auth_reset_password_confirm', methods: ['POST'])]
    public function resetPasswordConfirm(): JsonResponse
    {
        return $this->responseFactory->single([
            'entity' => 'auth_reset_password',
            'action' => 'confirm',
            'fields' => [
                'token' => ['type' => 'string', 'required' => true, 'nullable' => false],
                'new_password' => ['type' => 'string', 'required' => true, 'nullable' => false, 'minLength' => 12],
            ],
        ]);
    }
}
