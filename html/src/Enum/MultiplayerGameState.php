<?php

namespace App\Enum;

enum MultiplayerGameState: string
{
    case LOBBY = 'lobby';
    case READY = 'ready';
    case COUNTDOWN = 'countdown';
    case IN_PROGRESS = 'in_progress';
    case FINISHED = 'finished';
    case ABANDONED = 'abandoned';

    public function label(): string
    {
        return match ($this) {
            self::LOBBY => 'En attente de joueurs',
            self::READY => 'Prêt à démarrer',
            self::COUNTDOWN => 'Compte à rebours',
            self::IN_PROGRESS => 'En cours',
            self::FINISHED => 'Terminée',
            self::ABANDONED => 'Abandonnée',
        };
    }

    public function isActive(): bool
    {
        return $this !== self::FINISHED && $this !== self::ABANDONED;
    }
}
