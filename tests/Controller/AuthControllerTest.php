<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

final class AuthControllerTest extends WebTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    public function testLoginWithValidCredentials(): void
    {
        $this->client->request(
            'POST',
            '/api/auth/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'email' => 'admin@changeit.de',
                'password' => 'password123',
            ])
        );

        $this->assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true);
        
        $this->assertArrayHasKey('tokens', $data);
        $this->assertArrayHasKey('access_token', $data['tokens']);
        $this->assertArrayHasKey('refresh_token', $data['tokens']);
        $this->assertIsString($data['tokens']['access_token']);
        $this->assertIsString($data['tokens']['refresh_token']);
    }

    public function testLoginWithInvalidCredentials(): void
    {
        $this->client->request(
            'POST',
            '/api/auth/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'email' => 'admin@changeit.de',
                'password' => 'wrongpassword',
            ])
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testLoginWithMissingEmail(): void
    {
        $this->client->request(
            'POST',
            '/api/auth/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'password' => 'password123',
            ])
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testLoginWithNonExistentUser(): void
    {
        $this->client->request(
            'POST',
            '/api/auth/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'email' => 'nonexistent@example.com',
                'password' => 'password123',
            ])
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testRefreshTokenWithValidToken(): void
    {
        $loginResponse = $this->login();
        $refreshToken = $loginResponse['tokens']['refresh_token'];

        $this->client->request(
            'POST',
            '/api/auth/refresh',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'refresh_token' => $refreshToken,
            ])
        );

        $this->assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true);
        
        $this->assertArrayHasKey('tokens', $data);
        $this->assertArrayHasKey('access_token', $data['tokens']);
        $this->assertArrayHasKey('refresh_token', $data['tokens']);
    }

    public function testRefreshTokenWithInvalidToken(): void
    {
        $this->client->request(
            'POST',
            '/api/auth/refresh',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'refresh_token' => 'invalid_token',
            ])
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testLogoutWithValidToken(): void
    {
        $loginResponse = $this->login();
        $accessToken = $loginResponse['tokens']['access_token'];
        $refreshToken = $loginResponse['tokens']['refresh_token'];

        $this->client->request(
            'POST',
            '/api/auth/logout',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $accessToken,
            ],
            json_encode([
                'refresh_token' => $refreshToken,
            ])
        );

        $this->assertResponseIsSuccessful();
    }

    public function testLogoutWithoutToken(): void
    {
        $this->client->request(
            'POST',
            '/api/auth/logout',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json']
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testRegisterWithValidData(): void
    {
        $uniqueEmail = 'newuser_' . time() . '@changeit.test';

        $this->client->request(
            'POST',
            '/api/auth/register',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'name' => 'John Doe',
                'email' => $uniqueEmail,
                'password' => 'NewPassword123!',
            ])
        );

        $this->assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true);
        
        $this->assertArrayHasKey('tokens', $data);
        $this->assertArrayHasKey('access_token', $data['tokens']);
        $this->assertArrayHasKey('refresh_token', $data['tokens']);
    }

    public function testRegisterWithExistingEmail(): void
    {
        $this->client->request(
            'POST',
            '/api/auth/register',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'name' => 'John Doe',
                'email' => 'admin@changeit.de',
                'password' => 'Password123!',
            ])
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_CONFLICT);
    }

    public function testRegisterWithWeakPassword(): void
    {
        $uniqueEmail = 'weakpass_' . time() . '@changeit.test';

        $this->client->request(
            'POST',
            '/api/auth/register',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'name' => 'John Doe',
                'email' => $uniqueEmail,
                'password' => '123',
            ])
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    private function login(): array
    {
        $this->client->request(
            'POST',
            '/api/auth/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'email' => 'admin@changeit.de',
                'password' => 'password123',
            ])
        );

        return json_decode($this->client->getResponse()->getContent(), true);
    }
}
