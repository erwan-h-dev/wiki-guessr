<?php

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class PlayerController extends AbstractController
{
    #[Route('/player/set-username', name: 'player_set_username', methods: ['POST'])]
    public function setUsername(Request $request, EntityManagerInterface $entityManager): Response
    {
        $player = $request->attributes->get('_player');
        if ($player === null) {
            return new JsonResponse(['success' => false, 'error' => 'Not authenticated'], 401);
        }

        $username = $request->request->get('username');
        $username = trim($username ?? '');

        if (strlen($username) < 2 || strlen($username) > 50) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Le nom doit contenir entre 2 et 50 caractères'
            ], 400);
        }

        $player->setUsername($username);
        $entityManager->flush();

        return new JsonResponse(['success' => true]);
    }
    #[Route('/player/update-username', name: 'player_update_username', methods: ['POST'])]
    public function updateUsername(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $player = $request->attributes->get('_player');

        if ($player === null) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Not authenticated'
            ], 401);
        }

        $data = json_decode($request->getContent(), true);
        $newUsername = $data['username'] ?? null;

        if (!$newUsername) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Username is required'
            ], 400);
        }

        $newUsername = trim($newUsername);

        if (strlen($newUsername) < 2 || strlen($newUsername) > 50) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Username must be between 2 and 50 characters'
            ], 400);
        }

        // Update player username
        $player->setUsername($newUsername);
        $entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'username' => $newUsername
        ]);
    }
}
