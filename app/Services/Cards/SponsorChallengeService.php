<?php

namespace App\Services\Cards;

use App\Models\GameSaves\GameMatch;
use App\Models\GameSaves\GameSave;
use App\Models\GameSaves\GameTeam;

/** Défis sponsor : évaluation de l'objectif et versement de la récompense après les matchs de la semaine. */
class SponsorChallengeService
{
    /**
     * Résout les défis sponsor (revenue_challenge) après les matchs de la semaine.
     * Pour chaque défi en attente, on cherche le match joué de l'équipe cette
     * semaine, on évalue l'objectif et on crédite la récompense si réussi.
     * Un défi dont l'équipe n'a pas joué reste en attente.
     * Appelé dans GameMatchController (finishMatch / simulateWeek).
     *
     * @return array<int, array> les résultats de la semaine (pour affichage)
     */
    public function resolveSponsorChallenges(GameSave $gameSave, int $week): array
    {
        $state   = $gameSave->gameState();
        $pending = $state->pendingSponsorChallenges();

        // Toujours réinitialiser le récap de la semaine, même sans défi.
        if (empty($pending)) {
            if (!empty($state->sponsorResults())) {
                $state->putSponsorResults([]);
                $gameSave->save();
            }
            return [];
        }

        $results   = [];
        $remaining = [];

        foreach ($pending as $challenge) {
            $teamId = (int) ($challenge['game_team_id'] ?? 0);

            $match = GameMatch::where('game_save_id', $gameSave->id)
                ->where('week', $week)
                ->where('status', 'played')
                ->where(function ($q) use ($teamId) {
                    $q->where('home_team_id', $teamId)
                      ->orWhere('away_team_id', $teamId);
                })
                ->first();

            // Pas de match joué cette semaine pour cette équipe → le défi reste en attente.
            if (!$match) {
                $remaining[] = $challenge;
                continue;
            }

            $isHome  = (int) $match->home_team_id === $teamId;
            $scored  = (int) ($isHome ? $match->home_score : $match->away_score);
            $against = (int) ($isHome ? $match->away_score : $match->home_score);

            $success = self::evaluateChallenge((string) ($challenge['challenge'] ?? ''), $scored, $against);
            $reward  = (int) ($challenge['reward'] ?? 0);

            if ($success) {
                $team = GameTeam::find($teamId);
                if ($team) {
                    $team->budget = ($team->budget ?? 0) + $reward;
                    $team->save();
                }
            }

            $results[] = [
                'game_team_id' => $teamId,
                'card_name'    => $challenge['card_name'] ?? 'Défi sponsor',
                'icon'         => $challenge['icon'] ?? '🤝',
                'challenge'    => $challenge['challenge'] ?? '',
                'reward'       => $reward,
                'success'      => $success,
                'scored'       => $scored,
                'against'      => $against,
                'week'         => $week,
            ];
        }

        $state->putPendingSponsorChallenges($remaining);
        $state->putSponsorResults($results); // récap de la dernière clôture
        $gameSave->save();

        return $results;
    }

    /**
     * Évalue un objectif de défi sponsor à partir du score du match
     * (du point de vue de l'équipe qui a souscrit le défi).
     */
    public static function evaluateChallenge(string $challenge, int $scored, int $against): bool
    {
        return match ($challenge) {
            'win'         => $scored > $against,
            // « Prime de l'exploit » : l'adversaire mieux classé est vérifié à l'activation de la carte.
            'upset'       => $scored > $against,
            'score_3'     => $scored >= 3,
            'clean_sheet' => $against === 0,
            'win_by_2'    => ($scored - $against) >= 2,
            'win_score_3' => $scored > $against && $scored >= 3,
            default       => false,
        };
    }
}
