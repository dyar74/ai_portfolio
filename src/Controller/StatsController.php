<?php

namespace App\Controller;

use App\Service\StatsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class StatsController extends AbstractController
{
    #[Route('/api/stats', name: 'api_stats', methods: ['GET'])]
    public function stats(StatsService $statsService): JsonResponse
    {
        $stats = $statsService->getStats();

        $response = new JsonResponse(['stats' => $stats, 'timestamp' => new \DateTimeImmutable()]);
        $response->headers->set('Cache-Control', 'no-store');

        return $response;
    }
}
