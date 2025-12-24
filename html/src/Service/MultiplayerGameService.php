<?php

namespace App\Service;

use App\Entity\Challenge;
use App\Entity\GameSession;
use App\Entity\MultiplayerGame;
use App\Entity\MultiplayerParticipant;
use App\Entity\Player;
use App\Enum\MultiplayerGameState;
use App\Repository\MultiplayerGameRepository;
use App\Repository\MultiplayerParticipantRepository;
use Doctrine\ORM\EntityManagerInterface;

class MultiplayerGameService
{
    private const CODE_CHARACTERS = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    private const CODE_LENGTH = 6;
    private const COUNTDOWN_SECONDS = 5;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private MultiplayerGameRepository $gameRepository,
        private MultiplayerParticipantRepository $participantRepository,
        private GameNavigationService $gameNavigationService,
    ) {}

    public function createGame(Player $creator, bool $isPublic, int $maxPlayers): MultiplayerGame
    {
        $game = new MultiplayerGame();
        $game->setCreator($creator);
        $game->setCode($this->generateUniqueCode());
        $game->setIsPublic($isPublic);
        $game->setMaxPlayers($maxPlayers);
        $game->setState(MultiplayerGameState::LOBBY);

        $this->entityManager->persist($game);
        $this->entityManager->flush();

        // Auto-add creator as first participant
        $this->joinGame($game, $creator);

        return $game;
    }

    public function generateUniqueCode(): string
    {
        do {
            $code = '';
            for ($i = 0; $i < self::CODE_LENGTH; $i++) {
                $code .= self::CODE_CHARACTERS[rand(0, strlen(self::CODE_CHARACTERS) - 1)];
            }
        } while ($this->gameRepository->findByCode($code) !== null);

        return $code;
    }

    public function joinGame(MultiplayerGame $game, Player $player): MultiplayerParticipant
    {
        // Check if player already in game
        if ($this->participantRepository->findByGameAndPlayer($game, $player)) {
            throw new \InvalidArgumentException('Player already in this game');
        }

        // Check if game is full
        if ($this->isGameFull($game)) {
            throw new \InvalidArgumentException('Game is full');
        }

        // Check if game is joinable
        if (!$this->canJoin($game, $player)) {
            throw new \InvalidArgumentException('Cannot join this game in its current state');
        }

        $participant = new MultiplayerParticipant();
        $participant->setMultiplayerGame($game);
        $participant->setPlayer($player);
        $participant->setIsReady(false);

        $this->entityManager->persist($participant);
        $game->addParticipant($participant);
        $this->entityManager->flush();

        return $participant;
    }

    public function leaveGame(MultiplayerGame $game, Player $player): void
    {
        $participant = $this->participantRepository->findByGameAndPlayer($game, $player);

        if ($participant === null) {
            throw new \InvalidArgumentException('Player not in this game');
        }

        // If game not started, remove participant
        if (!$game->getState()->isActive() || $game->getState() === MultiplayerGameState::LOBBY || $game->getState() === MultiplayerGameState::READY) {
            $game->removeParticipant($participant);

            $this->entityManager->remove($participant);
            $this->entityManager->flush();

            // If creator leaves and game not started, delete the game
            if ($game->getCreator() === $player && $game->getParticipants()->count() === 0) {
                $this->entityManager->remove($game);
                $this->entityManager->flush();
            }
        } else {
            // If game started, mark as abandoned
            $participant->setHasFinished(true);
            $this->entityManager->flush();
        }
    }

    public function selectChallenge(MultiplayerGame $game, Challenge $challenge): void
    {
        if ($game->getCreator() === null) {
            throw new \InvalidArgumentException('Game has no creator');
        }

        $game->setChallenge($challenge);
        $game->setCustomStartPage(null);
        $game->setCustomEndPage(null);
        $game->setState(MultiplayerGameState::READY);

        // Reset all ready flags when challenge is selected
        foreach ($game->getParticipants() as $participant) {
            $participant->setIsReady(false);
        }

        $this->entityManager->flush();
    }

    public function selectCustomChallenge(MultiplayerGame $game, string $startPage, string $endPage): void
    {
        if ($game->getCreator() === null) {
            throw new \InvalidArgumentException('Game has no creator');
        }

        if (trim($startPage) === '' || trim($endPage) === '') {
            throw new \InvalidArgumentException('Start and end pages cannot be empty');
        }

        if ($startPage === $endPage) {
            throw new \InvalidArgumentException('Start and end pages must be different');
        }

        $game->setChallenge(null);
        $game->setCustomStartPage(trim($startPage));
        $game->setCustomEndPage(trim($endPage));
        $game->setState(MultiplayerGameState::READY);

        // Reset all ready flags when challenge is selected
        foreach ($game->getParticipants() as $participant) {
            $participant->setIsReady(false);
        }

        $this->entityManager->flush();
    }

    public function setPlayerReady(MultiplayerParticipant $participant, bool $ready): void
    {
        $game = $participant->getMultiplayerGame();

        $participant->setIsReady($ready);

        // If all players are ready, change game state to READY
        if ($this->allPlayersReady($game) && $game->getState() === MultiplayerGameState::LOBBY) {
            $game->setState(MultiplayerGameState::READY);
        }
        $this->entityManager->flush();

    }

    public function startCountdown(MultiplayerGame $game): void
    {
        if ($game->getCreator() === null) {
            throw new \InvalidArgumentException('Game has no creator');
        }

        if ($game->getChallenge() === null && !$game->hasCustomChallenge()) {
            throw new \InvalidArgumentException('Challenge not selected');
        }

        if (!$this->allPlayersReady($game)) {
            throw new \InvalidArgumentException('Not all players are ready');
        }

        $game->setState(MultiplayerGameState::COUNTDOWN);
        $game->setCountdownStartedAt(new \DateTimeImmutable());

        $this->entityManager->flush();
    }

    public function startGame(MultiplayerGame $game): void
    {
        if ($game->getChallenge() === null && !$game->hasCustomChallenge()) {
            throw new \InvalidArgumentException('Challenge not selected');
        }

        $game->setState(MultiplayerGameState::IN_PROGRESS);
        $game->setGameStartedAt(new \DateTimeImmutable());

        $startPage = $game->getStartPage();
        $endPage = $game->getEndPage();

        // Create a GameSession for each participant
        foreach ($game->getParticipants() as $participant) {
            $gameSession = new GameSession();
            $gameSession->setPlayer($participant->getPlayer());
            $gameSession->setChallenge($game->getChallenge());

            // If custom challenge, set custom pages
            if ($game->hasCustomChallenge()) {
                $gameSession->setCustomStartPage($game->getCustomStartPage());
                $gameSession->setCustomEndPage($game->getCustomEndPage());
            }

            $gameSession->setStartTime(new \DateTime());
            $gameSession->setPath([$startPage]);
            $gameSession->setEvents([
                [
                    'type' => 'page_visit',
                    'page' => $startPage,
                    'timestamp' => (new \DateTime())->format('Y-m-d H:i:s'),
                ],
            ]);
            $gameSession->setMultiplayerParticipant($participant);

            $this->entityManager->persist($gameSession);
            $participant->setGameSession($gameSession);
        }

        $this->entityManager->flush();
    }

    public function playerFinished(MultiplayerParticipant $participant): void
    {
        $game = $participant->getMultiplayerGame();

        if ($game->getState() !== MultiplayerGameState::IN_PROGRESS) {
            throw new \InvalidArgumentException('Game is not in progress');
        }

        $finishedCount = 0;
        $finishPosition = 1;

        // Count how many players have already finished
        foreach ($game->getParticipants() as $p) {
            if ($p->hasFinished()) {
                $finishedCount++;
                $finishPosition++;
            }
        }

        $participant->setHasFinished(true);
        $participant->setFinishedAt(new \DateTimeImmutable());
        $participant->setFinishPosition($finishPosition);

        // If all players finished, end the game
        if ($this->allPlayersFinished($game)) {
            $game->setState(MultiplayerGameState::FINISHED);
            $game->setGameEndedAt(new \DateTimeImmutable());
        }

        $this->entityManager->flush();
    }

    public function abandonGame(MultiplayerGame $game): void
    {
        $game->setState(MultiplayerGameState::ABANDONED);
        $game->setGameEndedAt(new \DateTimeImmutable());

        $this->entityManager->flush();
    }

    public function getPublicGames(): array
    {
        return $this->gameRepository->findPublicGames();
    }

    public function getGameByCode(string $code): ?MultiplayerGame
    {
        return $this->gameRepository->findByCode($code);
    }

    public function canJoin(MultiplayerGame $game, Player $player): bool
    {
        $state = $game->getState();

        return $state === MultiplayerGameState::LOBBY || $state === MultiplayerGameState::READY;
    }

    public function isGameFull(MultiplayerGame $game): bool
    {
        return $game->getParticipants()->count() >= $game->getMaxPlayers();
    }

    public function allPlayersReady(MultiplayerGame $game): bool
    {
        if ($game->getParticipants()->isEmpty()) {
            return false;
        }

        foreach ($game->getParticipants() as $participant) {
            if (!$participant->isReady()) {
                return false;
            }
        }

        return true;
    }

    public function allPlayersFinished(MultiplayerGame $game): bool
    {
        if ($game->getParticipants()->isEmpty()) {
            return false;
        }

        foreach ($game->getParticipants() as $participant) {
            if (!$participant->hasFinished()) {
                return false;
            }
        }

        return true;
    }
}
