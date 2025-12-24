<?php

namespace App\Controller;

use App\Entity\GameSession;
use App\Enum\ChallengeMode;
use App\Repository\ChallengeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ChallengeController extends AbstractController
{
    #[Route('/challenges', name: 'challenges')]
    public function list(Request $request, ChallengeRepository $challengeRepository, EntityManagerInterface $entityManager): Response
    {
        $challenges = $challengeRepository->findByMode(ChallengeMode::SOLO);

        // Get the current player from request attributes (set by PlayerSubscriber)
        $player = $request->attributes->get('_player');

        // Get completed sessions for the current player
        $challengesData = [];
        foreach ($challenges as $challenge) {

            $session = null;

            if ($player) {
                $session = $entityManager->getRepository(GameSession::class)->findOneBy([
                    'player' => $player,
                    'completed' => true,
                    'challenge' => $challenge
                ]);
            }

            $challengesData[] = [
                'challenge' => $challenge,
                'session' => $session,
            ];
        }

        return $this->render('challenge/list.html.twig', [
            'challengesData' => $challengesData
        ]);
    }
}
