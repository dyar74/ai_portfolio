<?php

namespace App\Service;

use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Aggregates DemoRun statistics with caching.
 */
final class StatsService
{
    private const CACHE_KEY = 'demo_stats';
    private const CACHE_TTL = 300; // 5 minutes

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly AdapterInterface $cache,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function getStats(): array
    {
        return $this->cache->get(self::CACHE_KEY, function (ItemInterface $item) {
            $item->expiresAfter(self::CACHE_TTL);

            $query = $this->em->createQuery(
                'SELECT d.slug, COUNT(d.id) as runs, AVG(d.durationMs) as avgDuration,
                        SUM(CASE WHEN d.status = \'ok\' THEN 1 ELSE 0 END) as successes
                 FROM App\Entity\DemoRun d
                 WHERE d.createdAt >= :sevenDaysAgo
                 GROUP BY d.slug'
            );
            $query->setParameter('sevenDaysAgo', new \DateTimeImmutable('-7 days'));

            $results = $query->getArrayResult();

            $stats = [];
            foreach ($results as $row) {
                $stats[$row['slug']] = [
                    'runs' => (int) $row['runs'],
                    'avgDurationMs' => (int) ($row['avgDuration'] ?? 0),
                    'successRate' => $row['runs'] > 0 ? round($row['successes'] / $row['runs'] * 100, 1) : 0,
                ];
            }

            return $stats;
        });
    }
}
