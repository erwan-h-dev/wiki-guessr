<?php

namespace App\EventSubscriber;

use App\Service\PlayerService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Permet de gérer l'association des Players aux requêtes via des cookies.
 * Lorsqu'une requête est reçue, ce subscriber vérifie si un cookie de Player est présent.
 * Si oui, il récupère le Player correspondant, sinon il en crée un nouveau.
 * Le Player est ensuite attaché à la requête pour être accessible dans les contrôleurs via (_player).
 */
class PlayerSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly PlayerService $playerService
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 10],
            KernelEvents::RESPONSE => ['onKernelResponse', -10],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();

        // Récupère ou crée le Player basé sur le cookie
        $player = $this->playerService->getOrCreatePlayer($request);

        // Stocke le Player dans les attributs de la requête pour y accéder dans les contrôleurs
        $request->attributes->set('_player', $player);
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $response = $event->getResponse();

        // Si le Player existe dans les attributs et qu'il n'y a pas encore de cookie valide
        $player = $request->attributes->get('_player');
        if ($player !== null && !$this->playerService->hasValidPlayerCookie($request)) {
            $this->playerService->attachPlayerCookie($response, $player);
        }
    }
}
