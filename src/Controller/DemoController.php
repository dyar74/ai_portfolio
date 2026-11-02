<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\DemoExecutor;
use App\Service\DemoRegistry;
use App\Service\N8nServerErrorException;
use App\Service\N8nTimeoutException;
use App\Service\ValidationException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\UuidV4;

/**
 * Secure BFF: forwards validated, rate-limited requests to n8n webhooks.
 * The browser NEVER calls n8n directly.
 *
 * Thin orchestrator: maps exceptions to HTTP status codes.
 * Business logic lives in DemoExecutor.
 */
class DemoController extends AbstractController
{
    public function __construct(
        private readonly DemoRegistry $registry,
        private readonly DemoExecutor $executor,
    ) {}

    #[Route('/api/demo/{slug}', name: 'api_demo', methods: ['POST'])]
    public function run(
        string $slug,
        Request $request,
        RateLimiterFactory $demoLimiter,
    ): Response {
        // 1. Unknown demo → 404
        if (!$this->registry->has($slug)) {
            return $this->jsonResponse(404, ['error' => 'unknown_demo']);
        }

        // 2. Wrong content-type → 415
        if ($request->getContentTypeFormat() !== 'json') {
            return $this->jsonResponse(415, ['error' => 'unsupported_media_type']);
        }

        // 3. Rate limit → 429
        $ip = $request->getClientIp() ?: '0.0.0.0';
        $limiter = $demoLimiter->create($slug);
        $hit = $limiter->consume();
        if (!$hit->isAccepted()) {
            $retryAfter = max(1, $hit->getRemainingInterval()->getSeconds());
            $response = $this->jsonResponse(429, ['error' => 'rate_limited', 'retryAfter' => $retryAfter]);
            $response->headers->set('Retry-After', (string) $retryAfter);
            return $response;
        }

        // 4. Execute
        $payload = json_decode((string) $request->getContent(), true) ?? [];
        $locale = $this->resolveLocale($request, $payload);
        $requestId = (string) UuidV4::v7();

        try {
            $result = $this->executor->execute($slug, $payload, $locale, $requestId);
            return $this->jsonResponse(200, ['ok' => true, 'data' => $result, 'requestId' => $requestId]);
        } catch (ValidationException $e) {
            return $this->jsonResponse(422, ['error' => 'validation_failed', 'violations' => $e->getViolations()]);
        } catch (N8nTimeoutException) {
            return $this->jsonResponse(504, ['error' => 'demo_timeout']);
        } catch (N8nServerErrorException) {
            return $this->jsonResponse(502, ['error' => 'demo_error']);
        }
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function resolveLocale(Request $request, array $payload): string
    {
        $allowed = ['en', 'uk'];
        if (isset($payload['locale']) && in_array($payload['locale'], $allowed, true)) {
            return $payload['locale'];
        }

        $accept = $request->headers->get('Accept-Language', 'en');
        return str_starts_with($accept, 'uk') ? 'uk' : 'en';
    }

    /**
     * @param array<string, mixed> $data
     */
    private function jsonResponse(int $status, array $data): JsonResponse
    {
        $response = new JsonResponse($data, $status);
        $response->headers->set('Content-Type', 'application/json');
        $response->headers->set('Cache-Control', 'no-store');
        return $response;
    }
}
