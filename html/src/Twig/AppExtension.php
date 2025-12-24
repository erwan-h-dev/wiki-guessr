<?php

namespace App\Twig;

use App\Entity\Player;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

class AppExtension extends AbstractExtension implements GlobalsInterface
{
    public function __construct(
        private readonly RequestStack $requestStack,
    ) {}

    public function getGlobals(): array
    {
        $player = $this->getCurrentPlayer();

        return [
            'player' => $player,
        ];
    }

    private function getCurrentPlayer(): ?Player
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request) {
            return null;
        }

        // Récupère le player depuis les attributs de la requête (défini par PlayerSubscriber)
        return $request->attributes->get('_player');
    }
}
