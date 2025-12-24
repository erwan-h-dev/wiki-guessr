<?php

namespace App\Repository;

use App\Entity\Challenge;
use App\Enum\ChallengeMode;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Challenge>
 */
class ChallengeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Challenge::class);
    }

    /**
     * Find challenges that have a specific mode
     * @return Challenge[] Returns an array of Challenge objects
     */
    public function findByMode(ChallengeMode $mode): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('JSON_CONTAINS(c.modes, :mode) = 1')
            ->setParameter('mode', json_encode($mode->value))
            ->orderBy('c.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

//    /**
//     * @return Challenge[] Returns an array of Challenge objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('c')
//            ->andWhere('c.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('c.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Challenge
//    {
//        return $this->createQueryBuilder('c')
//            ->andWhere('c.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
