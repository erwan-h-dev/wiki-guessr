<?php

namespace App\Service;

use App\Entity\MultiplayerGame;
use App\Entity\MultiplayerParticipant;

class MultiplayerSyncService
{
    public function getGameState(MultiplayerGame $game): array
    {
        $state = $game->getState()->value;
        $participants = [];

        foreach ($game->getParticipants() as $participant) {
            $participants[] = $this->getParticipantData($participant);
        }

        // Build challenge data
        $challengeData = null;
        if ($game->getChallenge()) {
            $challengeData = [
                'id' => $game->getChallenge()->getId(),
                'name' => $game->getChallenge()->getName(),
                'startPage' => $game->getChallenge()->getStartPage(),
                'endPage' => $game->getChallenge()->getEndPage(),
                'difficulty' => $game->getChallenge()->getDifficulty(),
            ];
        }

        $response = [
            'state' => $state,
            'challenge' => $challengeData,
            'participants' => $participants,
            'isPublic' => $game->isPublic(),
            'maxPlayers' => $game->getMaxPlayers(),
            'code' => $game->getCode(),
        ];

        // Add countdown info if in countdown state
        if ($game->getCountdownStartedAt() !== null) {
            $response['countdownEndsAt'] = $game->getCountdownStartedAt()->getTimestamp() + 5;
        }

        // Add game start timestamp
        if ($game->getGameStartedAt() !== null) {
            $response['gameStartedAt'] = $game->getGameStartedAt()->getTimestamp();
        }

        // Add game end timestamp
        if ($game->getGameEndedAt() !== null) {
            $response['gameEndedAt'] = $game->getGameEndedAt()->getTimestamp();
        }

        return $response;
    }

    private function getParticipantData(MultiplayerParticipant $participant): array
    {
        $session = $participant->getGameSession();
        $durationSeconds = 0;
        $pageCount = 0;
        $currentPage = '';
        $lastActivity = time();

        if ($session !== null && $session->isCompleted()) {
            $durationSeconds = $session->getDurationSeconds() ?? 0;
            $pageCount = count($session->getPath());
            $path = $session->getPath();
            $currentPage = !empty($path) ? end($path) : '';
            $lastActivity = $session->getUpdatedAt()?->getTimestamp() ?? time();
        } elseif ($session !== null && $session->getStartTime() !== null) {
            // Calculate current duration if game is in progress
            $now = new \DateTime();
            $durationSeconds = (int) $now->getTimestamp() - (int) $session->getStartTime()->getTimestamp();
            $pageCount = count($session->getPath());
            $path = $session->getPath();
            $currentPage = !empty($path) ? end($path) : '';
            $lastActivity = $session->getUpdatedAt()?->getTimestamp() ?? time();
        }

        return [
            'id' => $participant->getId(),
            'userName' => $participant->getPlayer()->getUsername(),
            'playerId' => $participant->getPlayer()->getId(),
            'isReady' => $participant->isReady(),
            'hasFinished' => $participant->hasFinished(),
            'finishPosition' => $participant->getFinishPosition(),
            'durationSeconds' => $durationSeconds,
            'pageCount' => $pageCount,
            'currentPage' => $currentPage,
            'lastActivity' => $lastActivity,
            'gameSessionId' => $session?->getId(),
            'joinedAt' => $participant->getJoinedAt()->getTimestamp(),
        ];
    }

    /**
     * Generate a simple hash of the current game state for caching/change detection
     */
    public function getStateHash(MultiplayerGame $game): string
    {
        $stateData = [
            'state' => $game->getState()->value,
            'participantCount' => $game->getParticipants()->count(),
            'challengeId' => $game->getChallenge()?->getId(),
            'customStartPage' => $game->getCustomStartPage(),
            'customEndPage' => $game->getCustomEndPage(),
        ];

        // Add hashes of participant states
        foreach ($game->getParticipants() as $p) {
            $stateData[] = [
                'playerId' => $p->getPlayer()->getId(),
                'isReady' => $p->isReady(),
                'hasFinished' => $p->hasFinished(),
            ];
        }

        return md5(json_encode($stateData));
    }
}
