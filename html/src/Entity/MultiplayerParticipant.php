<?php

namespace App\Entity;

use App\Repository\MultiplayerParticipantRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MultiplayerParticipantRepository::class)]
#[ORM\Table(name: 'multiplayer_participant')]
#[ORM\UniqueConstraint(
    name: 'unique_player_per_game',
    columns: ['multiplayer_game_id', 'player_id']
)]
class MultiplayerParticipant
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'participants')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?MultiplayerGame $multiplayerGame;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Player $player = null;

    #[ORM\OneToOne(cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?GameSession $gameSession = null;

    #[ORM\Column]
    private bool $isReady = false;

    #[ORM\Column]
    private bool $hasFinished = false;

    #[ORM\Column(nullable: true)]
    private ?int $finishPosition = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $finishedAt = null;

    #[ORM\Column]
    private \DateTimeImmutable $joinedAt;

    public function __construct()
    {
        $this->joinedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMultiplayerGame(): MultiplayerGame
    {
        return $this->multiplayerGame;
    }

    public function setMultiplayerGame(MultiplayerGame $multiplayerGame): static
    {
        $this->multiplayerGame = $multiplayerGame;

        return $this;
    }

    public function getPlayer(): ?Player
    {
        return $this->player;
    }

    public function setPlayer(?Player $player): static
    {
        $this->player = $player;

        return $this;
    }

    public function getGameSession(): ?GameSession
    {
        return $this->gameSession;
    }

    public function setGameSession(?GameSession $gameSession): static
    {
        $this->gameSession = $gameSession;

        return $this;
    }

    public function isReady(): bool
    {
        return $this->isReady;
    }

    public function setIsReady(bool $isReady): static
    {
        $this->isReady = $isReady;

        return $this;
    }

    public function hasFinished(): bool
    {
        return $this->hasFinished;
    }

    public function setHasFinished(bool $hasFinished): static
    {
        $this->hasFinished = $hasFinished;

        return $this;
    }

    public function getFinishPosition(): ?int
    {
        return $this->finishPosition;
    }

    public function setFinishPosition(?int $finishPosition): static
    {
        $this->finishPosition = $finishPosition;

        return $this;
    }

    public function getFinishedAt(): ?\DateTimeImmutable
    {
        return $this->finishedAt;
    }

    public function setFinishedAt(?\DateTimeImmutable $finishedAt): static
    {
        $this->finishedAt = $finishedAt;

        return $this;
    }

    public function getJoinedAt(): \DateTimeImmutable
    {
        return $this->joinedAt;
    }
}
