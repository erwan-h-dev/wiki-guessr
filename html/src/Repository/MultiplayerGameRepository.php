<?php

namespace App\Repository;

use App\Entity\MultiplayerGame;
use App\Enum\MultiplayerGameState;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MultiplayerGame>
 */
class MultiplayerGameRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MultiplayerGame::class);
    }

    public function findByCode(string $code): ?MultiplayerGame
    {
        return $this->findOneBy(['code' => $code]);
    }

    /**
     * @return MultiplayerGame[]
     */
    public function findPublicGames(): array
    {
        return $this->createQueryBuilder('g')
            ->andWhere('g.isPublic = true')
            ->andWhere('g.state IN (:states)')
            ->setParameter('states', [MultiplayerGameState::LOBBY, MultiplayerGameState::READY])
            ->orderBy('g.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return MultiplayerGame[]
     */
    public function findActiveGames(): array
    {
        return $this->createQueryBuilder('g')
            ->andWhere('g.state IN (:states)')
            ->setParameter('states', [
                MultiplayerGameState::LOBBY,
                MultiplayerGameState::READY,
                MultiplayerGameState::COUNTDOWN,
                MultiplayerGameState::IN_PROGRESS,
            ])
            ->getQuery()
            ->getResult();
    }
}
