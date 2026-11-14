<?php

namespace App\Services\Matches\Engine;

use App\Models\GameSaves\GameTeam;
use Illuminate\Support\Collection;

/**
 * État d'un match en cours de simulation : les deux équipes (fixes) et tout ce qui évolue
 * tour après tour (ballon, endurance, bilans, déroulé, fautes). Passé de méthode en méthode
 * par MatchSimulator au lieu d'une douzaine de variables locales.
 */
class MatchState
{
    /** Bilans par joueur (clé = id en chaîne), au format de MatchStats::emptyPlayerStats(). @var array<string, array> */
    public array $playerStats = [];

    /** Endurance courante et maximale par joueur (clé = id en chaîne). @var array<string, float> */
    public array $stamina = [];
    public array $staminaMax = [];

    /** Bilans des deux équipes ('home' / 'away'). @var array<string, array<string, int>> */
    public array $teamStats = [
        'home' => ['goals' => 0, 'shots' => 0, 'passes' => 0, 'dribbles' => 0, 'duelsWon' => 0, 'duelsLost' => 0],
        'away' => ['goals' => 0, 'shots' => 0, 'passes' => 0, 'dribbles' => 0, 'duelsWon' => 0, 'duelsLost' => 0],
    ];

    /** Buts, déroulé action par action et fautes / cartons / blessures (format foulEvents du moteur client). */
    public array $goalEvents = [];
    public array $events = [];
    public array $foulEvents = [];

    /** Une seule blessure d'épuisement par joueur et par match ; un seul « Dépassement de soi » par joueur. @var array<int|string, true> */
    public array $exhaustedInjured = [];
    public array $heroicUsed = [];

    /** Équipe possédant le ballon ('home' / 'away'), zone (0..MAX_ZONE_INDEX), et face au gardien ? */
    public string $side = 'home';
    public int $zone = 0;
    public bool $frontOfKeeper = false;

    /** @param mixed $homeGK gardien à domicile (ou null) @param mixed $awayGK gardien visiteur (ou null) */
    public function __construct(
        public readonly GameTeam $home,
        public readonly GameTeam $away,
        public readonly Collection $homePlayers,
        public readonly Collection $awayPlayers,
        public readonly mixed $homeGK,
        public readonly mixed $awayGK,
        public readonly bool $criticalsEnabled,
    ) {
    }

    /** Tous les joueurs sur le terrain, domicile puis visiteur. */
    public function allPlayers(): Collection
    {
        return $this->homePlayers->concat($this->awayPlayers);
    }
}
