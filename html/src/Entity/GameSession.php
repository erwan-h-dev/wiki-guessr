<?php

namespace App\Entity;

use App\Repository\GameSessionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: GameSessionRepository::class)]
class GameSession
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $startTime = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $endTime = null;

    #[ORM\Column(nullable: true)]
    private ?int $durationSeconds = null;

    #[ORM\Column(type: Types::JSON)]
    private array $path = [];

    #[ORM\Column(type: Types::JSON)]
    private array $events = [];

    #[ORM\Column]
    private ?bool $completed = false;

    #[ORM\ManyToOne(targetEntity: Player::class, inversedBy: 'gameSessions')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Player $player = null;

    #[ORM\ManyToOne(targetEntity: Challenge::class, inversedBy: 'gameSessions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Challenge $challenge = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStartTime(): ?\DateTimeInterface
    {
        return $this->startTime;
    }

    public function setStartTime(\DateTimeInterface $startTime): static
    {
        $this->startTime = $startTime;

        return $this;
    }

    public function getEndTime(): ?\DateTimeInterface
    {
        return $this->endTime;
    }

    public function setEndTime(?\DateTimeInterface $endTime): static
    {
        $this->endTime = $endTime;

        return $this;
    }

    public function getDurationSeconds(): ?int
    {
        return $this->durationSeconds;
    }

    public function setDurationSeconds(?int $durationSeconds): static
    {
        $this->durationSeconds = $durationSeconds;

        return $this;
    }

    public function getPath(): array
    {
        return $this->path;
    }

    public function setPath(array $path): static
    {
        $this->path = $path;

        return $this;
    }

    public function getEvents(): array
    {
        return $this->events;
    }

    public function setEvents(array $events): static
    {
        $this->events = $events;

        return $this;
    }

    public function isCompleted(): ?bool
    {
        return $this->completed;
    }

    public function setCompleted(bool $completed): static
    {
        $this->completed = $completed;

        return $this;
    }

    public function getChallenge(): ?Challenge
    {
        return $this->challenge;
    }

    public function setChallenge(?Challenge $challenge): static
    {
        $this->challenge = $challenge;

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

    public function complete(): void
    {
        $this->endTime = new \DateTime();
        $this->completed = true;

        if ($this->startTime !== null) {
            $this->durationSeconds = $this->endTime->getTimestamp() - $this->startTime->getTimestamp();
        }
    }

    public function addPageVisit(string $pageTitle): void
    {
        $this->events[] = [
            'type' => 'page_visit',
            'page' => $pageTitle,
            'timestamp' => (new \DateTime())->format('Y-m-d H:i:s'),
        ];
    }

    public function addBackNavigation(string $fromPage, string $toPage): void
    {
        $this->events[] = [
            'type' => 'back_navigation',
            'from' => $fromPage,
            'to' => $toPage,
            'timestamp' => (new \DateTime())->format('Y-m-d H:i:s'),
        ];
    }
}
