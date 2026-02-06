<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

final class TaskControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private string $accessToken;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->accessToken = $this->login();
    }

    public function testListTasksAsAuthenticated(): void
    {
        $this->client->request(
            'GET',
            '/api/task/list',
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

    public function testListTasksWithoutAuthentication(): void
    {
        $this->client->request('GET', '/api/task/list');

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testListTasksWithFilters(): void
    {
        $this->client->request(
            'GET',
            '/api/task/list?status=open&priority=high',
            [],
            [],
            [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $this->accessToken,
            ]
        );

        $this->assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true);
        
        $this->assertArrayHasKey('items', $data);
        $this->assertIsArray($data['items']);
    }

    public function testListTasksWithPagination(): void
    {
        $this->client->request(
            'GET',
            '/api/task/list?offset=0&limit=5',
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
        $this->assertArrayHasKey('offset', $data);
        $this->assertArrayHasKey('limit', $data);
        $this->assertEquals(0, $data['offset']);
        $this->assertEquals(5, $data['limit']);
    }

    public function testCreateTask(): void
    {
        $this->client->request(
            'POST',
            '/api/task',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $this->accessToken,
            ],
            json_encode([
                'title' => 'Test Task ' . time(),
                'description' => 'This is a test task',
                'status' => 'open',
                'priority' => 'medium',
            ])
        );

        $this->assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true);
        
        $this->assertArrayHasKey('id', $data);
        $this->assertArrayHasKey('title', $data);
        $this->assertArrayHasKey('status', $data);
        $this->assertArrayHasKey('priority', $data);
    }

    public function testCreateTaskWithoutTitle(): void
    {
        $this->client->request(
            'POST',
            '/api/task',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $this->accessToken,
            ],
            json_encode([
                'description' => 'Task without title',
                'status' => 'open',
                'priority' => 'medium',
            ])
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testShowTask(): void
    {
        $taskId = $this->createTaskAndGetId();

        $this->client->request(
            'GET',
            '/api/task/' . $taskId,
            [],
            [],
            [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $this->accessToken,
            ]
        );

        $this->assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true);
        
        $this->assertArrayHasKey('id', $data);
        $this->assertArrayHasKey('title', $data);
        $this->assertEquals($taskId, $data['id']);
    }

    public function testShowNonExistentTask(): void
    {
        $fakeId = '00000000-0000-0000-0000-000000000000';

        $this->client->request(
            'GET',
            '/api/task/' . $fakeId,
            [],
            [],
            [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $this->accessToken,
            ]
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testUpdateTask(): void
    {
        $taskId = $this->createTaskAndGetId();

        $this->client->request(
            'PATCH',
            '/api/task/' . $taskId,
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $this->accessToken,
            ],
            json_encode([
                'title' => 'Updated Task Title',
                'priority' => 'high',
            ])
        );

        $this->assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true);
        
        $this->assertEquals('Updated Task Title', $data['title']);
        $this->assertEquals('high', $data['priority']);
    }

    public function testUpdateTaskStatus(): void
    {
        $taskId = $this->createTaskAndGetId();

        $this->client->request(
            'POST',
            '/api/task/' . $taskId . '/status',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $this->accessToken,
            ],
            json_encode([
                'status' => 'in_progress',
            ])
        );

        $this->assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true);
        
        $this->assertEquals('in_progress', $data['status']);
    }

    public function testDeleteTask(): void
    {
        $taskId = $this->createTaskAndGetId();

        $this->client->request(
            'DELETE',
            '/api/task/' . $taskId,
            [],
            [],
            [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $this->accessToken,
            ]
        );

        $this->assertResponseIsSuccessful();

        $this->client->request(
            'GET',
            '/api/task/' . $taskId,
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

    private function createTaskAndGetId(): string
    {
        $this->client->request(
            'POST',
            '/api/task',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $this->accessToken,
            ],
            json_encode([
                'title' => 'Task for testing ' . time(),
                'description' => 'Test task',
                'status' => 'open',
                'priority' => 'low',
            ])
        );

        $data = json_decode($this->client->getResponse()->getContent(), true);
        return $data['id'] ?? '';
    }
}
