<?php

namespace App\Service;

use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * Checks n8n health with caching.
 *
 * IMPORTANT: a down/unreachable n8n must NEVER produce a 500. The footer "n8n live"
 * badge polls this endpoint, so any failure (DNS, timeout, n8n down, Redis hiccup)
 * is mapped to `n8n: down` and the endpoint still returns 200.
 */
final class HealthChecker
{
    private const CACHE_KEY = 'n8n_health';
    private const CACHE_TTL = 30; // 30 seconds

    public function __construct(
        private readonly N8nClient $n8nClient,
        private readonly AdapterInterface $cache,
    ) {}

    /**
     * @return array{site: string, n8n: string, latencyMs: int}
     */
    public function check(): array
    {
        try {
            $result = $this->cache->get(self::CACHE_KEY, function (ItemInterface $item): array {
                $item->expiresAfter(self::CACHE_TTL);

                try {
                    return $this->n8nClient->checkHealth();
                } catch (\Throwable) {
                    // n8n unreachable / timeout / DNS failure -> report down, do not propagate.
                    return ['status' => 'down', 'latencyMs' => 0];
                }
            });
        } catch (\Throwable) {
            // Cache backend (Redis) unavailable or any unexpected error -> still report down.
            $result = ['status' => 'down', 'latencyMs' => 0];
        }

        $status = is_array($result) && ($result['status'] ?? null) === 'ok' ? 'ok' : 'down';
        $latencyMs = is_array($result) ? (int) ($result['latencyMs'] ?? 0) : 0;

        return [
            'site' => 'ok',
            'n8n' => $status,
            'latencyMs' => $latencyMs,
        ];
    }
}
