<?php

namespace App\Services\MatchEngine;

use App\Contracts\Randomizer;
use App\Helpers\FormationHelper;
use App\Models\GameSaves\GameInjury;
use App\Models\GameSaves\GamePositionTraining;
use App\Models\GameSaves\GameSanction;
use App\Models\GameSaves\GameTeam;
use App\Services\Concerns\EvaluatesPlayers;
use Illuminate\Support\Collection;

/**
 * Les joueurs d'un match simulé : qui est indisponible (blessé, suspendu, en
 * formation), qui démarre (avec remplacement des titulaires écartés par un malus
 * ou une indisponibilité), l'application en mémoire des bonus et malus « ce
 * match », le gardien, les joueurs de champ et le choix d'un joueur pour une action.
 *
 * Ne modifie jamais la base : les stats ajustées n'existent que le temps du match.
 */
class MatchRoster
{
    use EvaluatesPlayers;

    /** Joueurs indisponibles (blessés / suspendus), indexés par "save:semaine". */
    private array $unavailableCache = [];

    /** Reçoit le générateur aléatoire (rejouable avec une graine en test). */
    public function __construct(private Randomizer $random)
    {
    }

    /**
     * Ids des joueurs blessés ou suspendus pour un match de la semaine donnée
     * (mêmes critères que AiLineUpService::adjustLineupsForWeek).
     *
     * @return array<int>
     */
    public function unavailablePlayerIds(int $gameSaveId, int $week): array
    {
        return $this->unavailableCache["{$gameSaveId}:{$week}"] ??= array_values(array_unique(array_map('intval', array_merge(
            GameInjury::where('game_save_id', $gameSaveId)
                ->where('week_return', '>', $week)
                ->pluck('game_player_id')
                ->all(),
            GameSanction::where('game_save_id', $gameSaveId)
                ->where('week_return', '>', $week)
                ->where('weeks_suspended', '>', 0)
                ->pluck('game_player_id')
                ->all(),
            GamePositionTraining::unavailablePlayerIds($gameSaveId, $week),
        ))));
    }

    /**
     * Les titulaires d'une équipe (11 au plus) : contrats titulaires, moins les joueurs
     * écartés (malus « titulaire consigné » ou indisponibilité), chacun étant remplacé par
     * le meilleur remplaçant au même poste, à défaut par le meilleur remplaçant tous postes.
     */
    public function getStarters(GameTeam $team, array $benchedIds = []): Collection
    {
        $starters = $team->contracts
            ->filter(fn($c) => $c->is_starter && $c->gamePlayer)
            ->values();

        // Malus « titulaire consigné » : on écarte les joueurs visés et on
        // promeut, pour chacun, le meilleur remplaçant disponible au même
        // poste (principal ou secondaire), avec repli sur le meilleur
        // remplaçant toutes positions confondues si aucun ne correspond.
        if (!empty($benchedIds)) {
            $benchedIds       = array_map('intval', $benchedIds);
            $benchedContracts = $starters->filter(fn($c) => in_array((int) $c->game_player_id, $benchedIds, true));
            $starters         = $starters->reject(fn($c) => in_array((int) $c->game_player_id, $benchedIds, true))->values();

            if ($benchedContracts->isNotEmpty()) {
                $availableSubs = $team->contracts
                    ->filter(fn($c) => !$c->is_starter && $c->gamePlayer
                        && !in_array((int) $c->game_player_id, $benchedIds, true))
                    ->values();

                foreach ($benchedContracts as $benchedContract) {
                    $targetPos = $this->positionGroup($benchedContract->gamePlayer->position ?? '');

                    $replacement = $availableSubs
                        ->filter(fn($c) => in_array($targetPos, $this->playerPositionGroups($c->gamePlayer), true))
                        ->sortByDesc(fn($c) => $this->playerOverall($c->gamePlayer, $targetPos))
                        ->first()
                        ?? $availableSubs->sortByDesc(fn($c) => $this->playerOverall($c->gamePlayer))->first();

                    if (!$replacement) continue;

                    $starters      = $starters->push($replacement);
                    $availableSubs = $availableSubs->reject(fn($c) => $c->id === $replacement->id)->values();
                }
            }
        }

        return $starters
            ->map(fn($c) => $c->gamePlayer)
            ->values()
            ->take(11);
    }

    /**
     * Applique en mémoire les modificateurs "ce match" (boosts pre_match positifs
     * + debuffs subis négatifs, bornés à [1,100]) aux joueurs d'une équipe.
     * $mods = ['team' => [...], 'players' => [playerId => [...]]].
     * Les modèles ne sont jamais sauvegardés ici, donc l'effet reste limité au
     * match (la progression post-match recharge via find()).
     */
    public function applyMatchStatModifiers(Collection $players, array $mods): void
    {
        $teamDelta    = $mods['team'] ?? [];
        $playerDeltas = $mods['players'] ?? [];
        if (empty($teamDelta) && empty($playerDeltas)) return;

        foreach ($players as $p) {
            if (!$p) continue;

            $delta = $teamDelta;
            foreach (($playerDeltas[(int) $p->id] ?? []) as $stat => $v) {
                $delta[$stat] = ($delta[$stat] ?? 0) + (int) $v;
            }

            foreach ($delta as $stat => $amount) {
                if (isset($p->{$stat})) {
                    $p->{$stat} = max(1, min(100, (int) $p->{$stat} + (int) $amount));
                }
            }
        }
    }

    /** Le gardien de l'équipe : le joueur dont la somme des stats d'arrêt (mains + poing) est la plus élevée. */
    public function getGoalkeeper(Collection $players)
    {
        // Le gardien = joueur avec le plus de hand_save + punch_save
        return $players->sortByDesc(fn($p) => ($p->hand_save ?? 0) + ($p->punch_save ?? 0))->first();
    }

    /** Le joueur est-il un gardien d'après son poste ? */
    private function isGoalkeeper($player): bool
    {
        return FormationHelper::roleFromPosition($player->position ?? null) === 'GK';
    }

    /**
     * Joueurs de champ (sans gardien), miroir du client qui exclut `.goalkeeper`
     * des sélections de défenseurs/receveurs (engine/field.js, pickWeightedPlayerInZone).
     * Repli sur la collection complète si elle ne contient que des gardiens.
     */
    public function fieldPlayers(Collection $players): Collection
    {
        $field = $players->reject(fn($p) => $this->isGoalkeeper($p));

        return $field->isNotEmpty() ? $field : $players;
    }

    /** Sélectionne un joueur de champ adapté à l'action (biaisé vers les profils pertinents, comme côté client). */
    public function pickPlayerForAction(Collection $players, string $action)
    {
        // Toutes les actions résolues ici sont des actions de champ : le gardien
        // ne doit jamais tacler/intercepter au milieu (ses arrêts passent par $defGK).
        $players = $this->fieldPlayers($players);

        if ($players->isEmpty()) return null;

        $statKey = match ($action) {
            'shot', 'special'     => 'shot',
            'pass'                => 'pass',
            'dribble'             => 'dribble',
            'intercept'           => 'intercept',
            'tackle'              => 'tackle',
            'block'               => 'block',
            default               => null,
        };

        if ($statKey === null) {
            return $this->random->pick($players->all());
        }

        $candidates = $players->filter(fn($p) => ($p->{$statKey} ?? 0) > 30);
        $pool       = $candidates->isNotEmpty() ? $candidates : $players;

        return $this->random->pick($pool->all());
    }
}
