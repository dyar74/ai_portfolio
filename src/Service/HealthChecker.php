<?php

namespace App\Service;

use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Component\Cache\Adapter\AdapterInterface;

/**
 * Checks n8n health with caching.
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
        $result = $this->cache->get(self::CACHE_KEY, function (ItemInterface $item) {
            $item->expiresAfter(self::CACHE_TTL);
            return $this->n8nClient->checkHealth();
        });

        $status = $result['status'] ?? 'down';
        $latencyMs = $result['latencyMs'] ?? 0;

        return [
            'site' => 'ok',
            'n8n' => $status,
            'latencyMs' => $latencyMs,
        ];
    }
}
