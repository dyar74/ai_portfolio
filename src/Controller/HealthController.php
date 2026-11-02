<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\HealthChecker;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

/**
 * GET /api/health — pings n8n (cached 30s in Redis via HealthChecker).
 * The footer "n8n live" badge polls this endpoint.
 */
class HealthController extends AbstractController
{
    public function __construct(
        private readonly HealthChecker $healthChecker,
    ) {}

    #[Route('/api/health', name: 'api_health', methods: ['GET'])]
    public function health(): JsonResponse
    {
        $health = $this->healthChecker->check();

        $response = new JsonResponse([
            'site' => 'ok',
            'n8n' => $health['n8n'],
            'latencyMs' => $health['latencyMs'],
            'timestamp' => new \DateTimeImmutable(),
        ]);
        $response->headers->set('Cache-Control', 'no-store');

        return $response;
    }
}
