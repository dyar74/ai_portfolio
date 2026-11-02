<?php

namespace App\Controller;

use App\Service\N8nClient;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class LastExecutionsController extends AbstractController
{
    #[Route('/api/n8n/last-executions', name: 'api_n8n_last_executions', methods: ['GET'])]
    public function lastExecutions(N8nClient $n8nClient): JsonResponse
    {
        $executions = $n8nClient->getLastExecutions(5);

        $response = new JsonResponse(['executions' => $executions, 'timestamp' => new \DateTimeImmutable()]);
        $response->headers->set('Cache-Control', 'no-store');

        return $response;
    }
}
