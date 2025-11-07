<?php

declare(strict_types=1);

namespace App\Controller;

use App\Auth\Dto\ChangePasswordRequest;
use App\Auth\Dto\LoginRequest;
use App\Auth\Dto\LogoutRequest;
use App\Auth\Dto\RefreshTokenRequest;
use App\Auth\Dto\RegisterRequest;
use App\Auth\Dto\ResetPasswordConfirmRequest;
use App\Config\Service\ConfigService;
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
use Symfony\Component\HttpFoundation\Response;
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
        private readonly AuditLogger $auditLogger,
        private readonly ConfigService $configService
    ) {
    }

    #[Route('/login', name: 'api_auth_login', methods: ['POST'])]
    public function login(LoginRequest $request): JsonResponse
    {
        $user = $this->userRepository->findOneBy(['email' => strtolower($request->email)]);

        if (!$user instanceof User) {
            throw ApiProblemException::fromStatus(401, 'Unauthorized', 'Invalid credentials.', 'TOKEN_INVALID');
        }

        if (!$user->isActive()) {
            throw ApiProblemException::fromStatus(403, 'Forbidden', 'Account is inactive.', 'USED_ACCOUNT_IS_INACTIVE');
        }

        if (!$this->passwordHasher->isPasswordValid($user, $request->password)) {
            throw ApiProblemException::fromStatus(401, 'Unauthorized', 'Invalid credentials.', 'TOKEN_INVALID');
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
        $account = $this->requireActiveUser($user);

        $this->authTokenService->revokeForUser($request->refreshToken, $account);

        $this->auditLogger->record('auth.logout', $account, []);

        return $this->responseFactory->single(['message' => 'Logout successful.']);
    }

    #[Route('/change-password', name: 'api_auth_change_password', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function changePassword(ChangePasswordRequest $request, #[CurrentUser] ?UserInterface $user): JsonResponse
    {
        $account = $this->requireActiveUser($user);

        if (!$this->passwordHasher->isPasswordValid($account, $request->currentPassword)) {
            throw ApiProblemException::validation(['current_password' => ['Current password is incorrect.']]);
        }

        $this->userService->setPassword($account, $request->newPassword);

        $this->auditLogger->record('auth.change_password', $account, []);

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

    #[Route('/register', name: 'api_auth_register', methods: ['POST'])]
    public function register(RegisterRequest $request): JsonResponse
    {
        $email = strtolower($request->email);

        $this->configService->assertCompanyEmail($email);

        $existingByName = $this->userRepository->findOneBy(['name' => $request->name]);
        if ($existingByName instanceof User) {
            throw ApiProblemException::fromStatus(409, 'Conflict', 'Username already in use.', 'USERNAME_ALREADY_IN_USE');
        }

        $existingByEmail = $this->userRepository->findOneBy(['email' => $email]);
        if ($existingByEmail instanceof User) {
            throw ApiProblemException::fromStatus(409, 'Conflict', 'Email already in use.', 'EMAIL_ALREADY_IN_USE');
        }

        $user = $this->userService->create($request->name, $email, $request->password, false, []);

        $this->auditLogger->record('auth.register', $user, []);

        return $this->responseFactory->single([
            'user' => $this->userViewFactory->make($user),
            'message' => 'Registration received. Account pending activation.',
        ], [], Response::HTTP_CREATED);
    }

    private function requireActiveUser(?UserInterface $user): User
    {
        if (!$user instanceof User) {
            throw ApiProblemException::unauthorized('Authentication is required.');
        }

        if (!$user->isActive()) {
            throw ApiProblemException::fromStatus(403, 'Forbidden', 'Account is inactive.', 'USED_ACCOUNT_IS_INACTIVE');
        }

        return $user;
    }
}
