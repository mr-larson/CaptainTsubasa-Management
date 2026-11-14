<?php

namespace App\Services\Matches;

use App\Models\GameSaves\GameContract;
use App\Models\GameSaves\GameMatch;

/**
 * Repère domicile / extérieur de match_stats.
 *
 * Le moteur de match (JS) raisonne en internal / external (internal = équipe du
 * joueur humain) et, jusqu'au correctif, enregistrait internal → home même quand
 * l'humain jouait à l'extérieur : stats d'équipe, titulaires et camp des
 * événements étaient alors inversés. Le moteur normalise désormais lui-même
 * (orientToHomeAway) ; ce service répare les matchs déjà enregistrés.
 */
class MatchStatsOrientation
{
    /** Marqueur posé sur les stats déjà dans le bon repère. */
    public const MARKER = 'internal_is_home';

    /**
     * Les stats de ce match sont-elles inversées ? On compare les titulaires
     * notés « home » aux effectifs réels des deux équipes à la date du match ;
     * repli sur les buts quand le score n'est pas nul.
     */
    public function isInverted(GameMatch $match): bool
    {
        $stats = $match->match_stats;
        if (!is_array($stats) || !empty($stats[self::MARKER])) {
            return false;
        }

        $homeStarters = array_map('intval', $stats['starters']['home'] ?? []);
        if ($homeStarters) {
            $inHome = count(array_intersect($homeStarters, $this->squadIds($match, 'home')));
            $inAway = count(array_intersect($homeStarters, $this->squadIds($match, 'away')));
            if ($inHome !== $inAway) {
                return $inAway > $inHome;
            }
        }

        $homeGoals = $stats['teams']['home']['goals'] ?? null;
        $awayGoals = $stats['teams']['away']['goals'] ?? null;
        if ($homeGoals !== null && $awayGoals !== null && (int) $match->home_score !== (int) $match->away_score) {
            return (int) $homeGoals === (int) $match->away_score && (int) $awayGoals === (int) $match->home_score;
        }

        return false;
    }

    /** Remet un match dans le bon repère s'il est inversé. Retourne true si modifié. */
    public function repair(GameMatch $match): bool
    {
        if (!$this->isInverted($match)) {
            return false;
        }

        // Le camp des événements est inversé lui aussi : on repart du détail complet.
        $match->match_stats = self::flip($match->fullStats());
        $match->save();

        return true;
    }

    /** Miroir PHP de orientToHomeAway (resources/js/Pages/Match/engine/matchStats.js). */
    public static function flip(array $stats): array
    {
        $side = fn ($s) => $s === 'internal' ? 'external' : ($s === 'external' ? 'internal' : $s);

        if (isset($stats['teams']) && is_array($stats['teams'])) {
            $stats['teams'] = ['home' => $stats['teams']['away'] ?? null, 'away' => $stats['teams']['home'] ?? null];
        }
        if (isset($stats['starters']) && is_array($stats['starters'])) {
            $stats['starters'] = ['home' => $stats['starters']['away'] ?? [], 'away' => $stats['starters']['home'] ?? []];
        }
        if (isset($stats['events']) && is_array($stats['events'])) {
            $stats['events'] = array_map(function ($ev) use ($side) {
                if (!is_array($ev)) {
                    return $ev;
                }
                if (array_key_exists('team', $ev)) {
                    $ev['team'] = $side($ev['team']);
                }
                foreach (['attacker', 'defender'] as $role) {
                    if (isset($ev['meta']['meta'][$role]['team'])) {
                        $ev['meta']['meta'][$role]['team'] = $side($ev['meta']['meta'][$role]['team']);
                    }
                }
                return $ev;
            }, $stats['events']);
        }
        $stats[self::MARKER] = true;

        return $stats;
    }

    /** Joueurs d'un camp au moment du match : instantané si présent, sinon contrats actifs cette semaine-là. */
    private function squadIds(GameMatch $match, string $side): array
    {
        $snapshot = $match->match_stats['lineups'][$side]['players'] ?? null;
        if (is_array($snapshot)) {
            return array_map(fn ($p) => (int) ($p['id'] ?? 0), $snapshot);
        }

        $teamId = $side === 'home' ? $match->home_team_id : $match->away_team_id;

        return GameContract::query()
            ->where('game_save_id', $match->game_save_id)
            ->where('game_team_id', $teamId)
            ->activeAt((int) $match->week)
            ->pluck('game_player_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }
}
