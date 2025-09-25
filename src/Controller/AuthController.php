<?php

declare(strict_types=1);

namespace App\Controller;

use App\Auth\Dto\ChangePasswordRequest;
use App\Auth\Dto\LoginRequest;
use App\Auth\Dto\LogoutRequest;
use App\Auth\Dto\RefreshTokenRequest;
use App\Auth\Dto\ResetPasswordConfirmRequest;
use App\Auth\Service\AuthTokenService;
use App\Auth\Service\PasswordResetTokenService;
use App\Entity\User;
use App\Exception\ApiProblemException;
use App\Http\Response\ApiResponseFactory;
use App\Log\Service\AuditLogger;
use App\Repository\UserRepository;
use App\Security\Permission\PermissionRegistry;
use App\User\Service\UserService;
use App\User\View\UserViewFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[Route('/api/auth')]
final class AuthController extends AbstractController
{
    public function __construct(
        private readonly AuthTokenService $authTokenService,
        private readonly UserRepository $userRepository,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly EntityManagerInterface $entityManager,
        private readonly ApiResponseFactory $responseFactory,
        private readonly PermissionRegistry $permissionRegistry,
        private readonly PasswordResetTokenService $passwordResetTokenService,
        private readonly UserViewFactory $userViewFactory,
        private readonly UserService $userService,
        private readonly AuditLogger $auditLogger
    ) {
    }

    #[Route('/login', name: 'api_auth_login', methods: ['POST'])]
    public function login(LoginRequest $request): JsonResponse
    {
        $user = $this->userRepository->findOneBy(['email' => strtolower($request->email)]);

        if (!$user instanceof User) {
            throw ApiProblemException::unauthorized('Invalid credentials.');
        }

        if (!$user->isActive()) {
            throw ApiProblemException::forbidden('Account is inactive.');
        }

        if (!$this->passwordHasher->isPasswordValid($user, $request->password)) {
            throw ApiProblemException::unauthorized('Invalid credentials.');
        }

        $user->setLastLoginAt(new \DateTime());
        $this->entityManager->flush();

        $tokens = $this->authTokenService->issue($user);

        return $this->responseFactory->single([
            'tokens' => $tokens->toArray(),
            'user' => $this->userViewFactory->make($user),
            'permissions' => $this->permissionRegistry->resolve($user),
        ]);
    }

    #[Route('/refresh', name: 'api_auth_refresh', methods: ['POST'])]
    public function refresh(RefreshTokenRequest $request): JsonResponse
    {
        $result = $this->authTokenService->refresh($request->refreshToken);
        $tokenPair = $result['tokenPair'];
        $user = $result['user'];

        return $this->responseFactory->single([
            'tokens' => $tokenPair->toArray(),
            'user' => $this->userViewFactory->make($user),
            'permissions' => $this->permissionRegistry->resolve($user),
        ]);
    }

    #[Route('/logout', name: 'api_auth_logout', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function logout(LogoutRequest $request, #[CurrentUser] ?UserInterface $user): JsonResponse
    {
        if (!$user instanceof User) {
            throw ApiProblemException::unauthorized('Authentication is required.');
        }

        $this->authTokenService->revokeForUser($request->refreshToken, $user);

        $this->auditLogger->record('auth.logout', $user, []);

        return $this->responseFactory->single(['message' => 'Logout successful.']);
    }

    #[Route('/change-password', name: 'api_auth_change_password', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function changePassword(ChangePasswordRequest $request, #[CurrentUser] ?UserInterface $user): JsonResponse
    {
        if (!$user instanceof User) {
            throw ApiProblemException::unauthorized('Authentication is required.');
        }

        if (!$this->passwordHasher->isPasswordValid($user, $request->currentPassword)) {
            throw ApiProblemException::validation(['current_password' => ['Current password is incorrect.']]);
        }

        $this->userService->setPassword($user, $request->newPassword);

        $this->auditLogger->record('auth.change_password', $user, []);

        return $this->responseFactory->single(['message' => 'Password updated.']);
    }

    #[Route('/reset-password/confirm', name: 'api_auth_reset_password_confirm', methods: ['POST'])]
    public function resetPasswordConfirm(ResetPasswordConfirmRequest $request): JsonResponse
    {
        $resetToken = $this->passwordResetTokenService->consume($request->token);
        $user = $resetToken->getUser();

        if (!$user instanceof PasswordAuthenticatedUserInterface) {
            throw ApiProblemException::internal('Unable to update password.');
        }

        $this->userService->setPassword($user, $request->newPassword);

        $tokens = $this->authTokenService->issue($user);

        $this->auditLogger->record('auth.reset_password_confirm', $user, []);

        return $this->responseFactory->single([
            'tokens' => $tokens->toArray(),
            'user' => $this->userViewFactory->make($user),
            'permissions' => $this->permissionRegistry->resolve($user),
        ]);
    }
}
