<?php

namespace App\Repository;

use App\Entity\Player;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

/**
 * @extends ServiceEntityRepository<Player>
 */
class PlayerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Player::class);
    }

    public function findByUuid(string|Uuid $uuid): ?Player
    {
        $uuidString = $uuid instanceof Uuid ? $uuid->toRfc4122() : $uuid;
        return $this->findOneBy(['uuid' => $uuidString]);
    }

    public function save(Player $player, bool $flush = false): void
    {
        $this->getEntityManager()->persist($player);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Player $player, bool $flush = false): void
    {
        $this->getEntityManager()->remove($player);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}