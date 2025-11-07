<?php

declare(strict_types=1);

namespace App\Controller;

use App\Http\Response\ApiResponseFactory;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/info/user')]
final class UserInfoController extends AbstractController
{
    public function __construct(private readonly ApiResponseFactory $responseFactory)
    {
    }

    #[Route('', name: 'api_info_user_create', methods: ['POST'])]
    public function create(): JsonResponse
    {
        return $this->responseFactory->single([
            'entity' => 'user',
            'action' => 'create',
            'fields' => [
                'name' => ['type' => 'string', 'required' => true, 'nullable' => false, 'maxLength' => 32],
                'email' => ['type' => 'string', 'required' => true, 'nullable' => false, 'format' => 'email', 'maxLength' => 128, 'unique' => true],
                'password' => ['type' => 'string', 'required' => true, 'nullable' => false, 'minLength' => 12, 'maxLength' => 255],
                'active' => ['type' => 'boolean', 'required' => false, 'nullable' => false, 'default' => true],
                'roles' => ['type' => 'array', 'items' => ['type' => 'uuid'], 'required' => false, 'nullable' => false],
            ],
            'errors' => ['VALIDATION_ERROR', 'CONFLICT'],
        ]);
    }

    #[Route('/update', name: 'api_info_user_update_admin', methods: ['POST'])]
    public function updateAdmin(): JsonResponse
    {
        return $this->responseFactory->single([
            'entity' => 'user',
            'action' => 'update',
            'fields' => [
                'name' => ['type' => 'string', 'required' => false, 'nullable' => false, 'maxLength' => 32],
                'email' => ['type' => 'string', 'required' => false, 'nullable' => false, 'format' => 'email', 'maxLength' => 128],
                'active' => ['type' => 'boolean', 'required' => false, 'nullable' => false],
                'roles' => ['type' => 'array', 'required' => false, 'items' => ['type' => 'uuid']],
            ],
            'errors' => ['VALIDATION_ERROR', 'CONFLICT', 'USED_ACCOUNT_IS_INACTIVE'],
        ]);
    }

    #[Route('/self', name: 'api_info_user_update_self', methods: ['POST'])]
    public function updateSelf(): JsonResponse
    {
        return $this->responseFactory->single([
            'entity' => 'user_self',
            'action' => 'update',
            'fields' => [
                'name' => ['type' => 'string', 'required' => false, 'nullable' => false, 'maxLength' => 32],
            ],
            'errors' => ['VALIDATION_ERROR', 'USED_ACCOUNT_IS_INACTIVE'],
        ]);
    }

    #[Route('/reset-password', name: 'api_info_user_reset_password_self', methods: ['POST'])]
    public function resetPasswordSelf(): JsonResponse
    {
        return $this->responseFactory->single([
            'entity' => 'user_reset_password_self',
            'action' => 'request',
            'fields' => [],
            'errors' => ['TOKEN_INVALID', 'USED_ACCOUNT_IS_INACTIVE'],
        ]);
    }

    #[Route('/verify-email-for-password-reset', name: 'api_info_user_verify_email_for_password_reset', methods: ['POST'])]
    public function verifyEmailForPasswordReset(): JsonResponse
    {
        return $this->responseFactory->single([
            'entity' => 'user_verify_password_reset_email',
            'action' => 'verify',
            'fields' => [
                'email' => ['type' => 'string', 'required' => true, 'nullable' => false, 'format' => 'email'],
            ],
            'errors' => ['RESOURCE_NOT_FOUND', 'USED_ACCOUNT_IS_INACTIVE', 'EMAIL_DOES_NOT_MATCH'],
        ]);
    }

    #[Route('/deactivate', name: 'api_info_user_deactivate', methods: ['POST'])]
    public function deactivate(): JsonResponse
    {
        return $this->responseFactory->single([
            'entity' => 'user',
            'action' => 'deactivate',
            'fields' => [],
            'errors' => ['RESOURCE_NOT_FOUND', 'USED_ACCOUNT_IS_INACTIVE'],
        ]);
    }

    #[Route('/reactivate', name: 'api_info_user_reactivate', methods: ['POST'])]
    public function reactivate(): JsonResponse
    {
        return $this->responseFactory->single([
            'entity' => 'user',
            'action' => 'reactivate',
            'fields' => [],
            'errors' => ['RESOURCE_NOT_FOUND', 'USED_ACCOUNT_IS_INACTIVE'],
        ]);
    }

    #[Route('/obfuscated-email', name: 'api_info_user_obfuscated_email', methods: ['POST'])]
    public function obfuscatedEmail(): JsonResponse
    {
        return $this->responseFactory->single([
            'entity' => 'user_obfuscated_email',
            'action' => 'read',
            'fields' => [
                'id' => ['type' => 'uuid', 'required' => true, 'nullable' => false, 'location' => 'path'],
            ],
            'errors' => ['RESOURCE_NOT_FOUND'],
        ]);
    }
}
