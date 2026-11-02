<?php

namespace App\Repository;

use App\Entity\DemoRun;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * @extends ServiceEntityRepository<DemoRun>
 */
class DemoRunRepository extends ServiceEntityRepository
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct($em, DemoRun::class);
        $this->em = $em;
    }

    /**
     * @return DemoRun[]
     */
    public function findOlderThan(\DateTimeImmutable $cutoff): array
    {
        return $this->createQueryBuilder('d')
            ->where('d.createdAt < :cutoff')
            ->setParameter('cutoff', $cutoff)
            ->getQuery()
            ->getResult();
    }

    public function purgeOlderThan(\DateTimeImmutable $cutoff): int
    {
        $runs = $this->findOlderThan($cutoff);
        foreach ($runs as $run) {
            $this->em->remove($run);
        }
        $this->em->flush();
        return count($runs);
    }
}
