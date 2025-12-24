<?php

namespace App\Repository;

use App\Entity\MultiplayerGame;
use App\Entity\MultiplayerParticipant;
use App\Entity\Player;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MultiplayerParticipant>
 */
class MultiplayerParticipantRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MultiplayerParticipant::class);
    }

    public function findByGameAndPlayer(MultiplayerGame $game, Player $player): ?MultiplayerParticipant
    {
        return $this->findOneBy([
            'multiplayerGame' => $game,
            'player' => $player,
        ]);
    }

    /**
     * @return MultiplayerParticipant[]
     */
    public function findByGame(MultiplayerGame $game): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.multiplayerGame = :game')
            ->setParameter('game', $game)
            ->orderBy('p.joinedAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return MultiplayerParticipant[]
     */
    public function findFinishedByGame(MultiplayerGame $game): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.multiplayerGame = :game')
            ->andWhere('p.hasFinished = true')
            ->setParameter('game', $game)
            ->orderBy('p.finishPosition', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
