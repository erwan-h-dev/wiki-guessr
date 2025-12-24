<?php

namespace App\Entity;

use App\Enum\ChallengeMode;
use App\Repository\ChallengeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ChallengeRepository::class)]
class Challenge
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    private ?string $startPage = null;

    #[ORM\Column(length: 255)]
    private ?string $endPage = null;

    #[ORM\Column(length: 50)]
    private ?string $difficulty = null;

    /**
     * @var array<string>
     */
    #[ORM\Column(type: Types::JSON)]
    private array $modes = [];

    /**
     * @var Collection<int, GameSession>
     */
    #[ORM\OneToMany(targetEntity: GameSession::class, mappedBy: 'challenge')]
    private Collection $gameSessions;

    /**
     * @var Collection<int, MultiplayerGame>
     */
    #[ORM\OneToMany(targetEntity: MultiplayerGame::class, mappedBy: 'challenge')]
    private Collection $multiplayerGames;

    public function __construct()
    {
        $this->gameSessions = new ArrayCollection();
        $this->multiplayerGames = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getStartPage(): ?string
    {
        return $this->startPage;
    }

    public function setStartPage(string $startPage): static
    {
        $this->startPage = $startPage;

        return $this;
    }

    public function getEndPage(): ?string
    {
        return $this->endPage;
    }

    public function setEndPage(string $endPage): static
    {
        $this->endPage = $endPage;

        return $this;
    }

    public function getDifficulty(): ?string
    {
        return $this->difficulty;
    }

    public function setDifficulty(string $difficulty): static
    {
        $this->difficulty = $difficulty;

        return $this;
    }

    /**
     * @return array<string>
     */
    public function getModes(): array
    {
        return $this->modes;
    }

    /**
     * @param array<string> $modes
     */
    public function setModes(array $modes): static
    {
        $this->modes = $modes;

        return $this;
    }

    /**
     * Add a mode to the challenge
     */
    public function addMode(string|ChallengeMode $mode): static
    {
        $modeValue = $mode instanceof ChallengeMode ? $mode->value : $mode;

        if (!in_array($modeValue, $this->modes, true)) {
            $this->modes[] = $modeValue;
        }

        return $this;
    }

    /**
     * Remove a mode from the challenge
     */
    public function removeMode(string|ChallengeMode $mode): static
    {
        $modeValue = $mode instanceof ChallengeMode ? $mode->value : $mode;

        $this->modes = array_values(array_filter(
            $this->modes,
            fn($m) => $m !== $modeValue
        ));

        return $this;
    }

    /**
     * Check if the challenge has a specific mode
     */
    public function hasMode(string|ChallengeMode $mode): bool
    {
        $modeValue = $mode instanceof ChallengeMode ? $mode->value : $mode;

        return in_array($modeValue, $this->modes, true);
    }

    /**
     * Get modes as ChallengeMode enum instances
     * @return array<ChallengeMode>
     */
    public function getModesAsEnum(): array
    {
        return array_map(
            fn($mode) => ChallengeMode::from($mode),
            $this->modes
        );
    }

    /**
     * @return Collection<int, GameSession>
     */
    public function getGameSessions(): Collection
    {
        return $this->gameSessions;
    }

    public function addGameSession(GameSession $gameSession): static
    {
        if (!$this->gameSessions->contains($gameSession)) {
            $this->gameSessions->add($gameSession);
            $gameSession->setChallenge($this);
        }

        return $this;
    }

    public function removeGameSession(GameSession $gameSession): static
    {
        if ($this->gameSessions->removeElement($gameSession)) {
            // set the owning side to null (unless already changed)
            if ($gameSession->getChallenge() === $this) {
                $gameSession->setChallenge(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, MultiplayerGame>
     */
    public function getMultiplayerGames(): Collection
    {
        return $this->multiplayerGames;
    }

    public function addMultiplayerGame(MultiplayerGame $multiplayerGame): static
    {
        if (!$this->multiplayerGames->contains($multiplayerGame)) {
            $this->multiplayerGames->add($multiplayerGame);
            $multiplayerGame->setChallenge($this);
        }

        return $this;
    }

    public function removeMultiplayerGame(MultiplayerGame $multiplayerGame): static
    {
        if ($this->multiplayerGames->removeElement($multiplayerGame)) {
            if ($multiplayerGame->getChallenge() === $this) {
                $multiplayerGame->setChallenge(null);
            }
        }

        return $this;
    }
}
