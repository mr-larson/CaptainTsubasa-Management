<?php

namespace App\Services\Cards;

use App\Models\GameSaves\GameMatch;
use App\Models\GameSaves\GameSave;

/**
 * Cartes « avant match » en cours : lecture des boosts et debuffs actifs d'une équipe
 * (lus par le simulateur et l'écran de match), puis consommation une fois le match joué.
 */
class PreMatchCardService
{
    /**
     * Retourne les boosts actifs pour une équipe donnée (pour le moteur JS via engineConfig).
     * Appelé dans GameMatchController@show avant de passer engineConfig.
     */
    public function getActivePreMatchBoosts(GameSave $gameSave, int $teamId): array
    {
        $cards = $gameSave->gameState()->activePreMatchCards();
        return array_filter($cards, fn ($c) => (int) $c['game_team_id'] === $teamId);
    }

    /**
     * Agrège les boosts pre_match actifs d'une équipe en deltas de stats, prêts
     * à être ajoutés aux stats des joueurs pour le match (cumul si plusieurs
     * cartes actives). Ex. : ['attack' => 15, 'stamina' => 20].
     * Lu par GameMatchController::mapPlayers (match joué) et MatchSimulator (IA).
     *
     * @return array<string, int>
     */
    public function getPreMatchStatBoosts(GameSave $gameSave, int $teamId): array
    {
        $delta = [];
        foreach ($this->getActivePreMatchBoosts($gameSave, $teamId) as $card) {
            $ev   = $card['effect_value'] ?? [];
            $stat = $ev['stat'] ?? null;
            if ($stat && isset($ev['amount'])) {
                $delta[$stat] = ($delta[$stat] ?? 0) + (int) $ev['amount'];
            }
            // Carte à plusieurs stats (« Remontada », « Rage de l'outsider ») : {stats: {stat => montant}}.
            foreach ((array) ($ev['stats'] ?? []) as $name => $amount) {
                $delta[$name] = ($delta[$name] ?? 0) + (int) $amount;
            }
            if (isset($ev['stamina_bonus'])) {
                $delta['stamina'] = ($delta['stamina'] ?? 0) + (int) $ev['stamina_bonus'];
            }
        }
        return $delta;
    }

    /**
     * Modificateurs de stats pour le match d'une équipe : boosts propres
     * (positifs) + debuffs reçus (négatifs). Sépare l'effet d'équipe (appliqué
     * à tous) de l'effet par joueur (cadre muselé).
     * Lu par mapPlayers et MatchSimulator.
     *
     * @return array{team: array<string,int>, players: array<int,array<string,int>>}
     */
    public function getMatchStatModifiers(GameSave $gameSave, int $teamId, ?int $matchId = null): array
    {
        $team    = $this->getPreMatchStatBoosts($gameSave, $teamId); // boosts propres (positifs)
        $players = $gameSave->gameState()->declarationBoosts((int) $matchId, $teamId);

        foreach ($gameSave->gameState()->pendingStatDebuffs() as $d) {
            if ((int) ($d['target_team_id'] ?? 0) !== $teamId) continue;

            $deltas = $d['deltas'] ?? [];
            $pid    = $d['target_player_id'] ?? null;

            if ($pid) {
                foreach ($deltas as $stat => $v) {
                    $players[(int) $pid][$stat] = ($players[(int) $pid][$stat] ?? 0) + (int) $v;
                }
            } else {
                foreach ($deltas as $stat => $v) {
                    $team[$stat] = ($team[$stat] ?? 0) + (int) $v;
                }
            }
        }

        return ['team' => $team, 'players' => $players];
    }

    /**
     * Consomme (retire) les cartes pre_match d'une équipe après le match.
     */
    public function consumePreMatchCards(GameSave $gameSave, int $teamId): void
    {
        $state = $gameSave->gameState();
        $state->putActivePreMatchCards(array_filter(
            $state->activePreMatchCards(),
            fn ($c) => (int) $c['game_team_id'] !== $teamId
        ));
        $gameSave->save();
    }

    /**
     * Consomme les cartes pre_match des équipes ayant joué cette semaine
     * (humaines ET IA). Appelé une fois en fin de semaine sur l'instance du
     * contrôleur — corrige aussi le cas des cartes IA jamais consommées.
     */
    public function consumePreMatchCardsForPlayedWeek(GameSave $gameSave, int $week): void
    {
        $state   = $gameSave->gameState();
        $cards   = $state->activePreMatchCards();
        $debuffs = $state->pendingStatDebuffs();
        if (empty($cards) && empty($debuffs)) return;

        $playedTeamIds = GameMatch::where('game_save_id', $gameSave->id)
            ->where('week', $week)
            ->where('status', 'played')
            ->get(['home_team_id', 'away_team_id'])
            ->flatMap(fn ($m) => [(int) $m->home_team_id, (int) $m->away_team_id])
            ->unique()
            ->all();

        if (empty($playedTeamIds)) return;

        // Boosts propres : consommés quand l'équipe propriétaire a joué.
        $state->putActivePreMatchCards(array_filter(
            $cards,
            fn ($c) => !in_array((int) ($c['game_team_id'] ?? 0), $playedTeamIds, true)
        ));

        // Debuffs reçus : consommés quand l'équipe CIBLE a joué.
        $state->putPendingStatDebuffs(array_filter(
            $debuffs,
            fn ($d) => !in_array((int) ($d['target_team_id'] ?? 0), $playedTeamIds, true)
        ));

        $gameSave->save();
    }
}
