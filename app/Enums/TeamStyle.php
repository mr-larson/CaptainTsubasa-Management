<?php

namespace App\Enums;

class TeamStyle
{
    // Tactical styles
    public const TACTICAL_OFFENSIVE     = 'offensive';
    public const TACTICAL_DEFENSIVE     = 'defensive';
    public const TACTICAL_POSSESSION    = 'possession';
    public const TACTICAL_COUNTER       = 'counter';
    public const TACTICAL_BALANCED      = 'balanced';

    public const TACTICAL_STYLES = [
        self::TACTICAL_OFFENSIVE,
        self::TACTICAL_DEFENSIVE,
        self::TACTICAL_POSSESSION,
        self::TACTICAL_COUNTER,
        self::TACTICAL_BALANCED,
    ];

    public const TACTICAL_LABELS = [
        self::TACTICAL_OFFENSIVE  => 'Offensif',
        self::TACTICAL_DEFENSIVE  => 'Défensif',
        self::TACTICAL_POSSESSION => 'Possession',
        self::TACTICAL_COUNTER    => 'Contre-attaque',
        self::TACTICAL_BALANCED   => 'Équilibré',
    ];

    public const TACTICAL_ICONS = [
        self::TACTICAL_OFFENSIVE  => '⚔️',
        self::TACTICAL_DEFENSIVE  => '🛡️',
        self::TACTICAL_POSSESSION => '🎯',
        self::TACTICAL_COUNTER    => '⚡',
        self::TACTICAL_BALANCED   => '⚖️',
    ];

    // Management philosophies
    public const PHILOSOPHY_STARS      = 'stars'; //4
    public const PHILOSOPHY_COLLECTIVE = 'collective';//3
    public const PHILOSOPHY_BALANCED   = 'balanced';//3
    public const PHILOSOPHY_ECONOMIST  = 'economist';//3

    public const PHILOSOPHIES = [
        self::PHILOSOPHY_STARS,
        self::PHILOSOPHY_COLLECTIVE,
        self::PHILOSOPHY_BALANCED,
        self::PHILOSOPHY_ECONOMIST,
    ];

    public const PHILOSOPHY_LABELS = [
        self::PHILOSOPHY_STARS      => 'Stars',
        self::PHILOSOPHY_COLLECTIVE => 'Collectif',
        self::PHILOSOPHY_BALANCED   => 'Équilibré',
        self::PHILOSOPHY_ECONOMIST  => 'Économe',
    ];

    public const PHILOSOPHY_ICONS = [
        self::PHILOSOPHY_STARS      => '⭐',
        self::PHILOSOPHY_COLLECTIVE => '👥',
        self::PHILOSOPHY_BALANCED   => '⚖️',
        self::PHILOSOPHY_ECONOMIST  => '💰',
    ];
}
