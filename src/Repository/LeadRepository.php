<?php

namespace App\Repository;

use App\Entity\Lead;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Lead>
 */
class LeadRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Lead::class);
    }

    /**
     * @return Lead[]
     */
    public function findExpired(\DateTimeImmutable $now): array
    {
        return $this->createQueryBuilder('l')
            ->where('l.deleteAfter < :now')
            ->andWhere('l.deleteAfter IS NOT NULL')
            ->setParameter('now', $now)
            ->getQuery()
            ->getResult();
    }

    public function purgeExpired(\DateTimeImmutable $now): int
    {
        $leads = $this->findExpired($now);
        foreach ($leads as $lead) {
            $this->_em->remove($lead);
        }
        $this->_em->flush();
        return count($leads);
    }
}
