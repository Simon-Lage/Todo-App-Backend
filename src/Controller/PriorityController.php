<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/priorities')]
final class PriorityController extends AbstractController
{
    private const array PRIORITIES = [
        'low' => 'Low',
        'medium' => 'Medium',
        'high' => 'High',
        'urgent' => 'Urgent'
    ];

    private const array STATUSES = [
        'open' => 'Open',
        'in_progress' => 'In Progress',
        'review' => 'Review',
        'done' => 'Done',
        'cancelled' => 'Cancelled'
    ];

    #[Route('', name: 'api_priorities_index', methods: ['GET'])]
    public function index(): JsonResponse
    {
        return $this->json([
            'priorities' => self::PRIORITIES,
            'statuses' => self::STATUSES
        ]);
    }

    #[Route('/priorities', name: 'api_priorities_list', methods: ['GET'])]
    public function priorities(): JsonResponse
    {
        return $this->json(self::PRIORITIES);
    }

    #[Route('/statuses', name: 'api_statuses_list', methods: ['GET'])]
    public function statuses(): JsonResponse
    {
        return $this->json(self::STATUSES);
    }
}
