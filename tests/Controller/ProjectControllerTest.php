<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

final class ProjectControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private string $accessToken;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->accessToken = $this->login();
    }

    public function testListProjects(): void
    {
        $this->client->request(
            'GET',
            '/api/project/list',
            [],
            [],
            [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $this->accessToken,
            ]
        );

        $this->assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true);
        
        $this->assertArrayHasKey('items', $data);
        $this->assertArrayHasKey('total', $data);
        $this->assertIsArray($data['items']);
    }

    public function testListProjectsWithoutAuthentication(): void
    {
        $this->client->request('GET', '/api/project/list');

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testCreateProject(): void
    {
        $this->client->request(
            'POST',
            '/api/project',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $this->accessToken,
            ],
            json_encode([
                'name' => 'Test Project ' . $this->uniqueSuffix(),
                'description' => 'This is a test project',
            ])
        );

        $this->assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true);
        
        $this->assertArrayHasKey('id', $data);
        $this->assertArrayHasKey('name', $data);
    }

    public function testCreateProjectWithoutName(): void
    {
        $this->client->request(
            'POST',
            '/api/project',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $this->accessToken,
            ],
            json_encode([
                'description' => 'Project without name',
            ])
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testShowProject(): void
    {
        $projectId = $this->createProjectAndGetId();

        $this->client->request(
            'GET',
            '/api/project/' . $projectId,
            [],
            [],
            [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $this->accessToken,
            ]
        );

        $this->assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true);
        
        $this->assertArrayHasKey('id', $data);
        $this->assertArrayHasKey('name', $data);
        $this->assertEquals($projectId, $data['id']);
    }

    public function testUpdateProject(): void
    {
        $projectId = $this->createProjectAndGetId();

        $this->client->request(
            'PATCH',
            '/api/project/' . $projectId,
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $this->accessToken,
            ],
            json_encode([
                'name' => 'Updated Project Name',
                'description' => 'Updated description',
            ])
        );

        $this->assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true);
        
        $this->assertEquals('Updated Project Name', $data['name']);
    }

    public function testDeleteProject(): void
    {
        $projectId = $this->createProjectAndGetId();

        $this->client->request(
            'DELETE',
            '/api/project/' . $projectId,
            [],
            [],
            [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $this->accessToken,
            ]
        );

        $this->assertResponseIsSuccessful();

        $this->client->request(
            'GET',
            '/api/project/' . $projectId,
            [],
            [],
            [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $this->accessToken,
            ]
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    private function login(): string
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

        $data = json_decode($this->client->getResponse()->getContent(), true);
        return $data['tokens']['access_token'] ?? '';
    }

    private function createProjectAndGetId(): string
    {
        $this->client->request(
            'POST',
            '/api/project',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $this->accessToken,
            ],
            json_encode([
                'name' => 'Project for testing ' . $this->uniqueSuffix(),
                'description' => 'Test project',
            ])
        );

        $data = json_decode($this->client->getResponse()->getContent(), true);
        return $data['id'] ?? '';
    }

    private function uniqueSuffix(): string
    {
        return str_replace('.', '', uniqid('', true));
    }
}
