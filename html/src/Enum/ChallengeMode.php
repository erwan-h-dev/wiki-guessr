<?php

namespace App\Enum;

enum ChallengeMode: string
{
    case SOLO = 'solo';
    case CHALLENGE_OF_DAY = 'challenge_of_day';
    case MULTIPLAYER = 'multiplayer';

    public function label(): string
    {
        return match ($this) {
            self::SOLO => 'Solo',
            self::CHALLENGE_OF_DAY => 'Défi du Jour',
            self::MULTIPLAYER => 'Multijoueur',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::SOLO => 'Défis classiques à jouer seul',
            self::CHALLENGE_OF_DAY => 'Un nouveau défi chaque jour',
            self::MULTIPLAYER => 'Compétition en temps réel'
        };
    }
}
