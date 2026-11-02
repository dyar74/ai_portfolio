<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\SiteContent;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SiteContent>
 */
class SiteContentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SiteContent::class);
    }

    /**
     * @return SiteContent[]
     */
    public function findByLocale(string $locale): array
    {
        return $this->createQueryBuilder('sc')
            ->where('sc.locale = :locale')
            ->setParameter('locale', $locale)
            ->orderBy('sc.key', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findOneByLocaleAndKey(string $locale, string $key): ?SiteContent
    {
        return $this->findOneBy(['locale' => $locale, 'key' => $key]);
    }
}
