<?php

namespace App\Enums;

/**
 * Origine d'une variation de moral ou de relation avec le coach. Les sept
 * premiers cas sont écrits dans game_player_morale_logs.source ; Training et
 * Match n'existent que dans le fil « changements récents » de
 * PlayerMoraleSummary (gains de stats), jamais en base.
 */
enum MoraleSource: string
{
    case Result        = 'result';
    case PlayingTime   = 'playing_time';
    case Salary        = 'salary';
    case Promise       = 'promise';
    case Declaration   = 'declaration';
    case BonusCard     = 'bonus_card';
    case CoachAffinity = 'coach_affinity';

    // Fil d'historique uniquement (non persistés dans game_player_morale_logs).
    case Training = 'training';
    case Match    = 'match';
}
