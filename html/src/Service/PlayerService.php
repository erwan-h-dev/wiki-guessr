<?php

namespace App\Service;

use App\Entity\Player;
use App\Repository\PlayerRepository;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Uid\Uuid;

class PlayerService
{
    private const COOKIE_PLAYER = 'wiki_guessr_player';
    private const COOKIE_LIFETIME = 365 * 24 * 60 * 60; // 1 an en secondes

    public function __construct(
        private readonly PlayerRepository $playerRepository
    ) {
    }

    /**
     * Récupère ou crée un Player basé sur le cookie de la requête
     */
    public function getOrCreatePlayer(Request $request): Player
    {
        $cookieValue = $request->cookies->get(self::COOKIE_PLAYER);

        if ($cookieValue !== null) {
            try {
                $uuid = Uuid::fromString($cookieValue);
                $player = $this->playerRepository->findByUuid($uuid);

                if ($player !== null) {
                    $player->updateLastSeenAt();
                    $this->playerRepository->save($player, true);
                    return $player;
                }
            } catch (\InvalidArgumentException $e) {
                // UUID invalide dans le cookie, on crée un nouveau player
            }
        }

        // Crée un nouveau player
        $player = new Player();
        $this->playerRepository->save($player, true);

        return $player;
    }

    /**
     * Crée un cookie pour associer le Player au navigateur
     */
    public function createPlayerCookie(Player $player): Cookie
    {
        return Cookie::create(self::COOKIE_PLAYER)
            ->withValue($player->getUuid())
            ->withExpires(time() + self::COOKIE_LIFETIME)
            ->withPath('/')
            ->withSecure(false) // Mettre à true en production avec HTTPS
            ->withHttpOnly(true)
            ->withSameSite(Cookie::SAMESITE_LAX);
    }

    /**
     * Attache le cookie du Player à une réponse
     */
    public function attachPlayerCookie(Response $response, Player $player): void
    {
        $response->headers->setCookie($this->createPlayerCookie($player));
    }

    /**
     * Vérifie si une requête a déjà un cookie Player valide
     */
    public function hasValidPlayerCookie(Request $request): bool
    {
        $cookieValue = $request->cookies->get(self::COOKIE_PLAYER);

        if ($cookieValue === null) {
            return false;
        }

        try {
            $uuid = Uuid::fromString($cookieValue);
            $player = $this->playerRepository->findByUuid($uuid);
            return $player !== null;
        } catch (\InvalidArgumentException $e) {
            return false;
        }
    }
}