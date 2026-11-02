<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\Contracts\HttpClient\TransportExceptionInterface;

/**
 * Client for communicating with the n8n instance.
 * Handles webhook calls with HMAC signing, retries, and timeout handling.
 *
 * Throws typed exceptions so the controller can map to HTTP status codes
 * by catch type (504 for timeout, 502 for server error) instead of
 * string-matching exception messages.
 */
final class N8nClient
{
    private const RETRYABLE_STATUS = [502, 503, 504];
    private const MAX_ATTEMPTS = 2; // initial + 1 retry
    private const RETRY_DELAY_US = 500_000; // 0.5s

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $baseUrl,
        private readonly string $webhookSecret,
        private readonly string $hmacKey,
        private readonly string $apiKey,
    ) {}

    /**
     * Send a webhook request to n8n.
     *
     * @param string $webhookPath The webhook path (e.g., "contact-to-telegram")
     * @param array<string, mixed> $payload The JSON payload to send
     * @return array<string, mixed> The decoded JSON response
     *
     * @throws N8nTimeoutException On timeout
     * @throws N8nServerErrorException On 5xx after retries exhausted
     */
    public function sendWebhook(string $webhookPath, array $payload, string $requestId = ''): array
    {
        $body = json_encode($payload, JSON_THROW_ON_ERROR);
        $signature = $this->hmacSign($body);
        $requestId = $requestId !== '' ? $requestId : (string) \Symfony\Component\Uid\UuidV4::v7();

        $url = $this->baseUrl . '/webhook/' . $webhookPath;
        $headers = [
            'Content-Type' => 'application/json',
            'X-Webhook-Secret' => $this->webhookSecret,
            'X-Signature' => 'sha256=' . $signature,
            'X-Request-Id' => $requestId,
        ];

        $response = $this->requestWithRetry('POST', $url, [
            'body' => $body,
            'headers' => $headers,
            'timeout' => 20,
        ]);

        $content = $response->getContent(false);
        return json_decode($content, true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * Check n8n health status.
     *
     * @return array{status: string, latencyMs: int}
     */
    public function checkHealth(): array
    {
        $start = microtime(true);
        try {
            $response = $this->httpClient->request('GET', $this->baseUrl . '/healthz', [
                'timeout' => 5,
            ]);
            $latencyMs = (int) ((microtime(true) - $start) * 1000);

            return [
                'status' => $response->getStatusCode() === 200 ? 'ok' : 'down',
                'latencyMs' => $latencyMs,
            ];
        } catch (TransportExceptionInterface) {
            return ['status' => 'down', 'latencyMs' => 0];
        }
    }

    /**
     * Get last executions from n8n Public API.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getLastExecutions(int $limit = 5): array
    {
        if ($this->apiKey === '') {
            return [];
        }

        try {
            $response = $this->httpClient->request('GET', $this->baseUrl . '/api/v1/executions', [
                'headers' => [
                    'X-N8N-API-KEY' => $this->apiKey,
                    'Accept' => 'application/json',
                ],
                'query' => [
                    'limit' => $limit,
                    'sort' => 'startedAt:desc',
                ],
                'timeout' => 10,
            ]);

            if ($response->getStatusCode() !== 200) {
                return [];
            }

            $data = json_decode($response->getContent(false), true);
            if (!is_array($data) || !isset($data['data']) || !is_array($data['data'])) {
                return [];
            }

            $executions = [];
            foreach ($data['data'] as $exec) {
                if (!is_array($exec)) {
                    continue;
                }
                $executions[] = [
                    'id' => $exec['id'] ?? null,
                    'workflowName' => $exec['workflowName'] ?? ($exec['workflow']['name'] ?? 'unknown'),
                    'status' => $exec['status'] ?? 'unknown',
                    'durationMs' => $exec['duration'] ?? null,
                    'startedAt' => $exec['startedAt'] ?? null,
                ];
            }

            return array_slice($executions, 0, $limit);
        } catch (TransportExceptionInterface) {
            return [];
        }
    }

    /**
     * Execute an HTTP request with retry-once on 502/503/504.
     * Throws typed exceptions on failure.
     *
     * @param array<string, mixed> $options
     */
    private function requestWithRetry(string $method, string $url, array $options): ResponseInterface
    {
        $lastException = null;

        for ($attempt = 1; $attempt <= self::MAX_ATTEMPTS; $attempt++) {
            try {
                $response = $this->httpClient->request($method, $url, $options);
                $statusCode = $response->getStatusCode();

                if (in_array($statusCode, self::RETRYABLE_STATUS, true) && $attempt < self::MAX_ATTEMPTS) {
                    usleep(self::RETRY_DELAY_US);
                    continue;
                }

                if ($statusCode >= 500) {
                    throw new N8nServerErrorException($statusCode);
                }

                return $response;
            } catch (TransportExceptionInterface $e) {
                $lastException = $e;
                // Timeout is not retryable (it's deterministic — the server is slow)
                if (str_contains($e->getMessage(), 'timeout') || str_contains($e->getMessage(), 'Timeout')) {
                    throw new N8nTimeoutException('n8n request timed out', 0, $e);
                }
                // Other transport errors: retry if we have attempts left
                if ($attempt < self::MAX_ATTEMPTS) {
                    usleep(self::RETRY_DELAY_US);
                    continue;
                }
                throw new N8nServerErrorException(0, 'n8n request failed: ' . $e->getMessage(), $e);
            }
        }

        // Should not reach here, but satisfy the type system
        throw new N8nServerErrorException(0, 'n8n request failed after retries', $lastException);
    }

    /**
     * Compute HMAC-SHA256 signature.
     */
    private function hmacSign(string $body): string
    {
        if ($this->hmacKey === '') {
            return hash('sha256', $body);
        }

        return hash_hmac('sha256', $body, $this->hmacKey);
    }
}
