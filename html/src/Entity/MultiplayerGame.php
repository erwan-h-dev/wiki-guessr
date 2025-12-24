<?php

namespace App\Entity;

use App\Enum\MultiplayerGameState;
use App\Repository\MultiplayerGameRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MultiplayerGameRepository::class)]
#[ORM\Table(name: 'multiplayer_game')]
class MultiplayerGame
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 10, unique: true)]
    private string $code;

    #[ORM\Column]
    private bool $isPublic = true;

    #[ORM\Column]
    private int $maxPlayers = 4;

    #[ORM\Column(enumType: MultiplayerGameState::class)]
    private MultiplayerGameState $state = MultiplayerGameState::LOBBY;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Challenge $challenge = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $customStartPage = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $customEndPage = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Player $creator = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $countdownStartedAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $gameStartedAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $gameEndedAt = null;

    /**
     * @var Collection<int, MultiplayerParticipant>
     */
    #[ORM\OneToMany(targetEntity: MultiplayerParticipant::class, mappedBy: 'multiplayerGame', cascade: ['remove'])]
    private Collection $participants;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->participants = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): static
    {
        $this->code = $code;

        return $this;
    }

    public function isPublic(): bool
    {
        return $this->isPublic;
    }

    public function setIsPublic(bool $isPublic): static
    {
        $this->isPublic = $isPublic;

        return $this;
    }

    public function getMaxPlayers(): int
    {
        return $this->maxPlayers;
    }

    public function setMaxPlayers(int $maxPlayers): static
    {
        $this->maxPlayers = $maxPlayers;

        return $this;
    }

    public function getState(): MultiplayerGameState
    {
        return $this->state;
    }

    public function setState(MultiplayerGameState $state): static
    {
        $this->state = $state;
        $this->updatedAt = new \DateTimeImmutable();

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

    public function getCustomStartPage(): ?string
    {
        return $this->customStartPage;
    }

    public function setCustomStartPage(?string $customStartPage): static
    {
        $this->customStartPage = $customStartPage;

        return $this;
    }

    public function getCustomEndPage(): ?string
    {
        return $this->customEndPage;
    }

    public function setCustomEndPage(?string $customEndPage): static
    {
        $this->customEndPage = $customEndPage;

        return $this;
    }

    public function getStartPage(): string
    {
        return $this->customStartPage ?? $this->challenge?->getStartPage() ?? '';
    }

    public function getEndPage(): string
    {
        return $this->customEndPage ?? $this->challenge?->getEndPage() ?? '';
    }

    public function hasCustomChallenge(): bool
    {
        return $this->customStartPage !== null && $this->customEndPage !== null;
    }

    public function getCreator(): ?Player
    {
        return $this->creator;
    }

    public function setCreator(?Player $creator): static
    {
        $this->creator = $creator;

        return $this;
    }

    public function getCountdownStartedAt(): ?\DateTimeImmutable
    {
        return $this->countdownStartedAt;
    }

    public function setCountdownStartedAt(?\DateTimeImmutable $countdownStartedAt): static
    {
        $this->countdownStartedAt = $countdownStartedAt;

        return $this;
    }

    public function getGameStartedAt(): ?\DateTimeImmutable
    {
        return $this->gameStartedAt;
    }

    public function setGameStartedAt(?\DateTimeImmutable $gameStartedAt): static
    {
        $this->gameStartedAt = $gameStartedAt;

        return $this;
    }

    public function getGameEndedAt(): ?\DateTimeImmutable
    {
        return $this->gameEndedAt;
    }

    public function setGameEndedAt(?\DateTimeImmutable $gameEndedAt): static
    {
        $this->gameEndedAt = $gameEndedAt;

        return $this;
    }

    /**
     * @return Collection<int, MultiplayerParticipant>
     */
    public function getParticipants(): Collection
    {
        return $this->participants;
    }

    public function addParticipant(MultiplayerParticipant $participant): static
    {
        if (!$this->participants->contains($participant)) {
            $this->participants->add($participant);
            $participant->setMultiplayerGame($this);
        }

        return $this;
    }

    public function removeParticipant(MultiplayerParticipant $participant): static
    {
        $this->participants->removeElement($participant);

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }
}
