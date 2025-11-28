<?php

namespace App\Controller;

use App\Repository\ChallengeRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ChallengeController extends AbstractController
{
    #[Route('/challenges', name: 'challenges')]
    public function list(ChallengeRepository $challengeRepository): Response
    {
        $challenges = $challengeRepository->findAll();

        return $this->render('challenge/list.html.twig', [
            'challenges' => $challenges,
        ]);
    }
}
