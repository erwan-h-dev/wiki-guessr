<?php

namespace App\Controller;

use App\Entity\Challenge;
use App\Entity\MultiplayerGame;
use App\Enum\ChallengeMode;
use App\Repository\ChallengeRepository;
use App\Service\MultiplayerGameService;
use App\Service\MultiplayerSyncService;
use App\Service\WikipediaService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/multiplayer')]
#[IsGranted('ROLE_USER')]
class MultiplayerController extends AbstractController
{
    public function __construct(
        private MultiplayerGameService $multiplayerGameService,
        private MultiplayerSyncService $multiplayerSyncService,
        private ChallengeRepository $challengeRepository,
        private WikipediaService $wikipediaService
    ) {}

    #[Route('/', name: 'multiplayer_lobby', methods: ['GET'])]
    public function lobby(Request $request): Response
    {
        $games = $this->multiplayerGameService->getPublicGames();
        $player = $request->attributes->get('_player');

        return $this->render('multiplayer/lobby.html.twig', [
            'games' => $games,
            'player' => $player,
        ]);
    }

    #[Route('/create', name: 'multiplayer_create', methods: ['POST'])]
    public function create(Request $request): Response
    {
        $isPublic = $request->request->getBoolean('isPublic', true);
        $maxPlayers = (int) $request->request->get('maxPlayers', 4);

        // Validate input
        if ($maxPlayers < 2 || $maxPlayers > 10) {
            $maxPlayers = 4;
        }

        $player = $request->attributes->get('_player');

        $game = $this->multiplayerGameService->createGame($player, $isPublic, $maxPlayers);

        return $this->redirectToRoute('multiplayer_room', ['id' => $game->getId()]);
    }

    #[Route('/{id}/room', name: 'multiplayer_room', methods: ['GET'])]
    public function room(MultiplayerGame $game, ChallengeRepository $challengeRepository, Request $request): Response
    {
        $player = $request->attributes->get('_player');

        if ($player === null) {
            throw $this->createAccessDeniedException();
        }
        // Check if player is in the game
        $isParticipant = false;
        foreach ($game->getParticipants() as $participant) {
            if ($participant->getPlayer() === $player) {
                $isParticipant = true;
                break;
            }
        }

        if (!$isParticipant) {
            throw $this->createNotFoundException('Player not in this game');
        }

        // Get available challenges for multiplayer
        $challenges = $challengeRepository->findByMode(ChallengeMode::MULTIPLAYER);

        return $this->render('multiplayer/room.html.twig', [
            'game' => $game,
            'challenges' => $challenges
        ]);
    }

    #[Route('/join/{code}', name: 'multiplayer_join_code', methods: ['GET'])]
    public function joinCode(string $code, Request $request): Response
    {
        $game = $this->multiplayerGameService->getGameByCode($code);

        if ($game === null) {
            throw $this->createNotFoundException('Game not found');
        }

        $player = $request->attributes->get('_player');

        if ($player === null) {
            throw $this->createAccessDeniedException();
        }

        // Check if player already in game
        foreach ($game->getParticipants() as $participant) {
            if ($participant->getPlayer() === $player) {
                return $this->redirectToRoute('multiplayer_room', ['id' => $game->getId()]);
            }
        }

        // Try to join
        try {
            $this->multiplayerGameService->joinGame($game, $player);
        } catch (\InvalidArgumentException $e) {
            $this->addFlash('error', $e->getMessage());
            return $this->redirectToRoute('multiplayer_lobby');
        }

        return $this->redirectToRoute('multiplayer_room', ['id' => $game->getId()]);
    }

    #[Route('/{id}/join', name: 'multiplayer_join', methods: ['POST'])]
    public function join(MultiplayerGame $game, Request $request): Response
    {
        $player = $request->attributes->get('_player');

        if ($player === null) {
            throw $this->createAccessDeniedException();
        }

        try {
            $this->multiplayerGameService->joinGame($game, $player);
        } catch (\InvalidArgumentException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('multiplayer_room', ['id' => $game->getId()]);
    }

    #[Route('/{id}/leave', name: 'multiplayer_leave', methods: ['POST'])]
    public function leave(MultiplayerGame $game, Request $request): Response
    {
        $player = $request->attributes->get('_player');

        if ($player === null) {
            throw $this->createAccessDeniedException();
        }

        try {
            $this->multiplayerGameService->leaveGame($game, $player);
        } catch (\InvalidArgumentException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('multiplayer_lobby');
    }

    #[Route('/{id}/kick/{participantId}', name: 'multiplayer_kick', methods: ['POST'])]
    public function kick(MultiplayerGame $game, int $participantId, Request $request): JsonResponse
    {
        $player = $request->attributes->get('_player');

        if ($game->getCreator() !== $player) {
            throw $this->createAccessDeniedException('Only creator can kick players');
        }

        $participant = null;
        foreach ($game->getParticipants() as $participant) {
            if ($participant->getId() === $participantId) {

                if ($participant->getPlayer() === $player) {
                    return new JsonResponse(['success' => false, 'error' => 'Cannot kick the creator'], 400);
                }

                 try {
                    $this->multiplayerGameService->leaveGame($game, $participant->getPlayer());
                    return new JsonResponse(['success' => true]);
                } catch (\InvalidArgumentException $e) {
                    return new JsonResponse(['success' => false, 'error' => $e->getMessage()], 400);
                }
            }
        }

        return new JsonResponse(['success' => false, 'error' => 'Participant not found'], 404);
    }

    #[Route('/{id}/challenge', name: 'multiplayer_select_challenge', methods: ['POST'])]
    public function selectChallenge(MultiplayerGame $game, Request $request): Response
    {
        $player = $request->attributes->get('_player');

        if ($game->getCreator() !== $player) {
            throw $this->createAccessDeniedException('Only creator can select challenge');
        }

        $challengeId = $request->request->getInt('challengeId');
        $customStartPage = $request->request->get('customStartPage');
        $customEndPage = $request->request->get('customEndPage');

        // If custom challenge is provided
        if ($customStartPage && $customEndPage) {
            try {
                $this->multiplayerGameService->selectCustomChallenge($game, $customStartPage, $customEndPage);
            } catch (\InvalidArgumentException $e) {
                $this->addFlash('error', $e->getMessage());
            }
            return $this->redirectToRoute('multiplayer_room', ['id' => $game->getId()]);
        }

        // Otherwise use predefined challenge
        $challenge = $this->challengeRepository->find($challengeId);

        if ($challenge === null) {
            throw $this->createNotFoundException('Challenge not found');
        }

        try {
            $this->multiplayerGameService->selectChallenge($game, $challenge);
        } catch (\InvalidArgumentException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('multiplayer_room', ['id' => $game->getId()]);
    }

    #[Route('/api/search-wikipedia', name: 'api_search_wikipedia', methods: ['GET'])]
    public function searchWikipedia(Request $request): JsonResponse
    {
        $query = $request->query->get('q', '');

        if (strlen($query) < 2) {
            return new JsonResponse(['results' => []]);
        }

        try {
            $results = $this->wikipediaService->searchPages($query, 10);
            return new JsonResponse(['results' => $results['titles']]);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], 500);
        }
    }

    #[Route('/{id}/ready', name: 'multiplayer_ready', methods: ['POST'])]
    public function ready(MultiplayerGame $game, Request $request): Response
    {
        $player = $request->attributes->get('_player');

        if ($player === null) {
            throw $this->createAccessDeniedException();
        }

        $ready = $request->request->getBoolean('ready');

        // Find participant
        $participant = null;
        foreach ($game->getParticipants() as $participant) {
            if ($participant->getPlayer() === $player) {
                try {
                    $this->multiplayerGameService->setPlayerReady($participant, $ready);
                } catch (\InvalidArgumentException $e) {
                    $this->addFlash('error', $e->getMessage());
                }
                return new JsonResponse(['success' => true]);
            }
        }

        throw $this->createNotFoundException('Participant not found');
    }

    #[Route('/{id}/start', name: 'multiplayer_start', methods: ['POST'])]
    public function start(MultiplayerGame $game, Request $request): JsonResponse
    {
        $player = $request->attributes->get('_player');

        if ($game->getCreator() !== $player) {
            throw $this->createAccessDeniedException('Only creator can start game');
        }

        try {
            $this->multiplayerGameService->startCountdown($game);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['success' => false, 'error' => $e->getMessage()], 400);
        }

        return new JsonResponse(['success' => true, 'countdownEndsAt' => (new \DateTimeImmutable())->getTimestamp() + 5]);
    }

    #[Route('/{id}/do-start', name: 'multiplayer_do_start', methods: ['POST'])]
    public function doStart(MultiplayerGame $game, Request $request): JsonResponse
    {
        $player = $request->attributes->get('_player');

        if ($game->getCreator() !== $player) {
            throw $this->createAccessDeniedException('Only creator can start game');
        }

        try {
            $this->multiplayerGameService->startGame($game);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['success' => false, 'error' => $e->getMessage()], 400);
        }

        return new JsonResponse(['success' => true]);
    }

    #[Route('/{id}/sync', name: 'multiplayer_sync', methods: ['GET'])]
    public function sync(MultiplayerGame $game, Request $request): JsonResponse
    {
        $player = $request->attributes->get('_player');

        if ($player === null) {
            throw $this->createAccessDeniedException();
        }

        // Check if player is in the game
        $isParticipant = false;
        foreach ($game->getParticipants() as $participant) {
            if ($participant->getPlayer() === $player) {
                $isParticipant = true;
                break;
            }
        }

        $response = $this->multiplayerSyncService->getGameState($game);

        return new JsonResponse($response);
    }

    #[Route('/{id}/abandon', name: 'multiplayer_abandon', methods: ['POST'])]
    public function abandon(MultiplayerGame $game, Request $request): Response
    {
        $player = $request->attributes->get('_player');

        if ($game->getCreator() !== $player) {
            throw $this->createAccessDeniedException('Only creator can abandon game');
        }

        try {
            $this->multiplayerGameService->abandonGame($game);
        } catch (\InvalidArgumentException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('multiplayer_lobby');
    }

    #[Route('/{id}/results', name: 'multiplayer_results', methods: ['GET'])]
    public function results(MultiplayerGame $game, Request $request): Response
    {
        $player = $request->attributes->get('_player');

        if ($player === null) {
            throw $this->createAccessDeniedException();
        }

        // Check if player is in the game
        $isParticipant = false;
        foreach ($game->getParticipants() as $participant) {
            if ($participant->getPlayer() === $player) {
                $isParticipant = true;
                break;
            }
        }

        if (!$isParticipant) {
            throw $this->createNotFoundException('Player not in this game');
        }

        // Get sorted participants by finish position
        $participants = $game->getParticipants();
        $finishedParticipants = [];
        $pendingParticipants = [];

        foreach ($participants as $participant) {
            if ($participant->hasFinished()) {
                $finishedParticipants[] = $participant;
            } else {
                $pendingParticipants[] = $participant;
            }
        }

        // Sort finished participants by finish position
        usort($finishedParticipants, function ($a, $b) {
            return ($a->getFinishPosition() ?? PHP_INT_MAX) <=> ($b->getFinishPosition() ?? PHP_INT_MAX);
        });

        return $this->render('multiplayer/results.html.twig', [
            'game' => $game,
            'finishedParticipants' => $finishedParticipants,
            'pendingParticipants' => $pendingParticipants,
            'player' => $player,
        ]);
    }
}
