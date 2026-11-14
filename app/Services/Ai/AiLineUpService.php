<?php

namespace App\Services\Ai;

use App\Enums\TeamStyle;
use App\Helpers\FormationHelper;
use App\Models\GameSaves\GameContract;
use App\Models\GameSaves\GameInjury;
use App\Models\GameSaves\GameMatch;
use App\Models\GameSaves\GamePositionTraining;
use App\Models\GameSaves\GameSanction;
use App\Models\GameSaves\GameSave;
use App\Models\GameSaves\GameTeam;
use App\Services\Concerns\EvaluatesPlayers;
use App\Support\GameState;
use Illuminate\Support\Collection;
use App\Services\Cards\BonusCardActivationService;

/**
 * Composition d'équipe automatique des équipes IA avant leurs matchs.
 * Remplace les titulaires indisponibles (blessés, suspendus, en formation, sur le banc par carte), adapte le schéma tactique à l'effectif, fait tourner les joueurs fatigués selon la philosophie de l'équipe et désigne un capitaine.
 * Écrit dans les contrats, la formation de l'équipe et la composition de l'état de la partie.
 */
class AiLineUpService
{
    use EvaluatesPlayers;

    /**
     * Seuils de stamina par philosophie.
     * En dessous du seuil, l'IA préfère faire tourner.
     */
    private const STAMINA_THRESHOLDS = [
        TeamStyle::PHILOSOPHY_STARS      => 45,  // Pousse les stars, rotate seulement si épuisé
        TeamStyle::PHILOSOPHY_COLLECTIVE => 65,  // Rotation régulière
        TeamStyle::PHILOSOPHY_BALANCED   => 60,  // Mix raisonnable
        TeamStyle::PHILOSOPHY_ECONOMIST  => 70,  // Rotation agressive, préserve tout le monde
    ];

    // Écart maximal de niveau et avance de fraîcheur pour une rotation volontaire.
    private const ROTATION_POLICY = [
        TeamStyle::PHILOSOPHY_COLLECTIVE => ['gap' => 6, 'keeper' => 4, 'field' => 7, 'field_limit' => 2],
        TeamStyle::PHILOSOPHY_BALANCED   => ['gap' => 4, 'keeper' => 5, 'field' => 9, 'field_limit' => 1],
        TeamStyle::PHILOSOPHY_ECONOMIST  => ['gap' => 6, 'keeper' => 3, 'field' => 5, 'field_limit' => 2],
    ];

    private const FORMATION_CHANGE_MARGIN = 8;
    private const STYLE_ZONE_WEIGHTS = [
        TeamStyle::TACTICAL_OFFENSIVE => [3 => 1, 4 => 2],
        TeamStyle::TACTICAL_DEFENSIVE => [1 => 2, 2 => 1],
        TeamStyle::TACTICAL_POSSESSION => [2 => 2, 3 => 2],
        TeamStyle::TACTICAL_COUNTER => [1 => 1, 2 => 1, 4 => 1],
    ];
    private const ZONE_POSITIONS = ['GK', 'DEF', 'MID', 'MID', 'ATT'];

    /** Prépare les équipes IA qui ont un match programmé cette semaine ($week). Ne fait rien pendant une pause de mi-saison en attente. */
    public function prepareScheduledTeams(GameSave $gameSave, int $week): void
    {
        if ($gameSave->hasPendingMidseasonBreak()) return;

        $teamIds = GameMatch::where('game_save_id', $gameSave->id)
            ->where('week', $week)
            ->where('status', 'scheduled')
            ->get(['home_team_id', 'away_team_id'])
            ->flatMap(fn ($match) => [$match->home_team_id, $match->away_team_id])
            ->unique()
            ->all();

        if ($teamIds) {
            $this->adjustLineupsForWeek($gameSave, $teamIds);
        }
    }

    /**
     * Ajuste le lineup de toutes les équipes IA avant un match.
     * 1) Réévalue les titulaires selon philosophie + stamina
     * 2) Remplace les indisponibles (blessés/suspendus)
     */
    public function adjustLineupsForWeek(GameSave $gameSave, ?array $teamIds = null): void
    {
        $currentWeek       = $gameSave->week ?? 1;
        $controlledTeamIds = array_merge($gameSave->controlledGameTeamIds(), [(int) $gameSave->controlled_game_team_id]);

        $injuredIds = GameInjury::where('game_save_id', $gameSave->id)
            ->where('week_return', '>', $currentWeek)
            ->pluck('game_player_id')
            ->toArray();

        $suspendedIds = GameSanction::where('game_save_id', $gameSave->id)
            ->where('week_return', '>', $currentWeek)
            ->where('weeks_suspended', '>', 0)
            ->pluck('game_player_id')
            ->toArray();

        $unavailableIds = array_unique(array_merge(
            $injuredIds, $suspendedIds,
            GamePositionTraining::unavailablePlayerIds($gameSave->id, $currentWeek),
        ));

        $aiTeams = GameTeam::where('game_save_id', $gameSave->id)
            ->whereNotIn('id', $controlledTeamIds)
            ->when($teamIds !== null, fn ($query) => $query->whereIn('id', $teamIds))
            ->with(['contracts' => fn($q) => $q->activeAt($currentWeek)->with('gamePlayer')])
            ->get();

        $state = $gameSave->gameState();
        $cards = app(BonusCardActivationService::class);

        foreach ($aiTeams as $team) {
            $unavailableForMatch = array_merge($unavailableIds, $cards->getBenchedPlayerIds($gameSave, $team->id));
            $adapted = $this->adaptFormation($team, $unavailableForMatch, $state);

            $rotation = $state->aiRotation($team->id);
            $season = (int) $gameSave->season;
            if (($rotation['season'] ?? null) !== $season || ($rotation['week'] ?? null) !== (int) $currentWeek) {
                $restingRoles = $adapted ? [] : $this->applyPhilosophyRotation($team, $unavailableForMatch, $state);
                $state->setAiRotation($team->id, $season, (int) $currentWeek, $restingRoles);
            }
            if (!$adapted) $this->adjustTeamLineup($team, $unavailableIds, $state);
            $this->assignCaptain($team, $unavailableForMatch);
        }

        $gameSave->save();
    }

    /** Conserve un capitaine éligible, sinon choisit le meilleur titulaire disponible. */
    private function assignCaptain(GameTeam $team, array $unavailableIds): void
    {
        $eligible = $team->contracts->filter(fn ($contract) => $contract->is_starter
            && $contract->gamePlayer
            && !in_array($contract->game_player_id, $unavailableIds))
            ->sortBy('id');

        $captain = $eligible->firstWhere('is_captain', true)
            ?? $eligible->sortByDesc(fn ($contract) => $this->playerOverall($contract->gamePlayer))->first();

        // Un seul capitaine par équipe et par saison (contrainte unique) : les
        // contrats expirés qui portent encore le brassard doivent le perdre aussi.
        GameContract::where('game_team_id', $team->id)
            ->where('is_captain', true)
            ->when($captain, fn ($query) => $query->where('id', '!=', $captain->id))
            ->update(['is_captain' => false]);

        foreach ($team->contracts as $contract) {
            $isCaptain = $captain !== null && $contract->id === $captain->id;
            if ((bool) $contract->is_captain === $isCaptain) continue;

            $contract->is_captain = $isCaptain;
            if ($isCaptain) {
                $contract->captain_rerolls_remaining = 3;
                $contract->captain_reroll_used_this_action = false;
            }
            $contract->save();
        }
    }

    /**
     * Cherche le meilleur schéma tactique pour les joueurs disponibles et compose le onze en conséquence. Retourne true si la composition a été modifiée.
     * Compare le schéma actuel aux autres (joueurs mal placés d'abord, puis score pondéré par le style tactique) et ne change que si le gain dépasse une marge. Met à jour les titulaires, la formation de l'équipe et la composition enregistrée.
     */
    private function adaptFormation(GameTeam $team, array $unavailableIds, GameState $state): bool
    {
        $current = $team->formation ?? FormationHelper::DEFAULT_FORMATION;
        $formations = FormationHelper::SLOT_ZONES;
        if (!isset($formations[$current])) return false;

        $available = $team->contracts
            ->filter(fn ($contract) => $contract->gamePlayer
                && !in_array($contract->game_player_id, $unavailableIds))
            ->unique('game_player_id');
        $byPosition = $available->groupBy(fn ($contract) => $this->positionGroup($contract->gamePlayer->position ?? ''));
        $keepers = $byPosition->get('GK', collect());
        if ($keepers->isEmpty() || $available->count() - $keepers->count() < 10) return false;

        $styleWeights = self::STYLE_ZONE_WEIGHTS[$team->tactical_style] ?? [];
        $starterPreference = $team->management_philosophy === TeamStyle::PHILOSOPHY_STARS ? 4 : 2;
        $candidates = [];
        foreach (array_unique(self::ZONE_POSITIONS) as $position) {
            $pool = $position === 'GK' ? $keepers : $available->diff($keepers);
            $candidates[$position] = $pool->map(function (GameContract $contract) use ($position, $starterPreference) {
                $player = $contract->gamePlayer;
                $fits = in_array($position, $this->playerPositionGroups($player), true);
                $score = $this->readiness($player, $position) * ($fits ? 1 : 0.65);

                return ['id' => (int) $player->id, 'fits' => $fits, 'score' => $score + ($contract->is_starter ? $starterPreference : 0)];
            })->sortByDesc('score')->keyBy('id');
        }

        $baseline = $this->scoreFormation($formations[$current], $candidates, $styleWeights);
        $bestFormation = $current;
        $best = $baseline;
        foreach ($formations as $formation => $zones) {
            if ($formation === $current) continue;
            $option = $this->scoreFormation($zones, $candidates, $styleWeights);
            if ($option['misplaced'] < $best['misplaced']
                || ($option['misplaced'] === $best['misplaced'] && $option['score'] > $best['score'])) {
                $bestFormation = $formation;
                $best = $option;
            }
        }

        if ($best['misplaced'] === $baseline['misplaced'] && $best['score'] < $baseline['score'] + self::FORMATION_CHANGE_MARGIN) {
            $bestFormation = $current;
            $best = $baseline;
        }

        $savedSlots = $state->lineupSlots($team->id) ?? [];
        $seen = [];
        $currentMisplaced = 0;
        foreach ($formations[$current] as $slot => $zone) {
            $playerId = (int) ($savedSlots[$slot] ?? 0);
            $candidate = $candidates[self::ZONE_POSITIONS[$zone]]->get($playerId);
            if (isset($seen[$playerId]) || !($candidate['fits'] ?? false)) {
                $currentMisplaced++;
            }
            $seen[$playerId] = true;
        }

        if ($bestFormation === $current && $currentMisplaced <= $best['misplaced']) {
            return false;
        }

        $slots = $best['slots'];
        foreach ($team->contracts as $contract) {
            $contract->is_starter = in_array((int) $contract->game_player_id, $slots, true);
            if ($contract->isDirty('is_starter')) $contract->save();
        }
        $team->formation = $bestFormation;
        $team->save();
        $state->setLineup($team->id, ['formation' => $bestFormation, 'slots' => $slots]);

        return true;
    }

    /** Note un schéma tactique : place les joueurs dans les postes de chaque zone, en respectant leurs postes possibles quand c'est faisable. Retourne les postes attribués ('slots'), le score total et le nombre de joueurs mal placés ('misplaced'). */
    private function scoreFormation(array $zones, array $candidates, array $styleWeights): array
    {
        $positions = array_map(fn ($zone) => self::ZONE_POSITIONS[$zone], $zones);
        $needs = array_count_values($positions);
        $surplus = [];
        foreach ($needs as $position => $count) {
            $surplus[$position] = $candidates[$position]->where('fits', true)->count() - $count;
        }
        asort($surplus);

        $slots = [];
        $used = [];
        foreach (array_keys($surplus) as $position) {
            foreach (array_keys($positions, $position, true) as $slot) {
                $visited = [];
                $this->assignCompatiblePlayer($slot, $positions, $candidates, $slots, $used, $visited);
            }
        }

        $misplaced = count($positions) - count($slots);
        $score = 0;
        foreach ($positions as $slot => $position) {
            if (!isset($slots[$slot])) {
                $candidate = $candidates[$position]->first(fn ($player) => !isset($used[$player['id']]));
                $slots[$slot] = $candidate['id'];
                $used[$candidate['id']] = $slot;
            }
            $score += $candidates[$position]->get($slots[$slot])['score'] + ($styleWeights[$zones[$slot]] ?? 0);
        }
        ksort($slots);

        return ['slots' => $slots, 'score' => $score, 'misplaced' => $misplaced];
    }

    /** Place un joueur compatible sur un poste ; si tous les compatibles sont déjà pris, tente de déplacer l'un d'eux ailleurs (parcours récursif de type affectation). Retourne true si le poste a pu être pourvu. */
    private function assignCompatiblePlayer(int $slot, array $positions, array $candidates, array &$slots, array &$used, array &$visited): bool
    {
        $pool = $candidates[$positions[$slot]];
        $free = $pool->first(fn ($candidate) => $candidate['fits'] && !isset($used[$candidate['id']]));
        if ($free) {
            $slots[$slot] = $free['id'];
            $used[$free['id']] = $slot;
            return true;
        }

        foreach ($pool as $id => $candidate) {
            if (!$candidate['fits'] || isset($visited[$id])) continue;
            $visited[$id] = true;
            if ($this->assignCompatiblePlayer($used[$id], $positions, $candidates, $slots, $used, $visited)) {
                $slots[$slot] = $id;
                $used[$id] = $slot;
                return true;
            }
        }

        return false;
    }

    /**
     * Fait tourner les titulaires fatigués et donne du temps aux doublures
     * proches en niveau. Retourne le poste laissé par les joueurs mis au repos.
     */
    protected function applyPhilosophyRotation(GameTeam $team, array $unavailableIds, GameState $state): array
    {
        $philosophy = $team->management_philosophy ?? TeamStyle::PHILOSOPHY_BALANCED;
        $threshold = self::STAMINA_THRESHOLDS[$philosophy] ?? self::STAMINA_THRESHOLDS[TeamStyle::PHILOSOPHY_BALANCED];
        $policy = self::ROTATION_POLICY[$philosophy] ?? null;
        $contracts = $team->contracts->filter(fn ($c) =>
            $c->gamePlayer && !in_array($c->game_player_id, $unavailableIds));
        $starters = $contracts->where('is_starter', true)->sortBy(fn ($c) => $c->gamePlayer->stamina);
        $subs = $contracts->where('is_starter', false)->filter(fn ($c) => $c->gamePlayer->stamina >= $threshold);
        $slots = array_map('intval', $state->lineupSlots($team->id) ?? []);
        $restingRoles = [];
        $fieldRotations = 0;

        foreach ($starters as $starter) {
            $player = $starter->gamePlayer;
            $position = $this->lineupPosition($team, $player->position, (int) $player->id, $slots);
            $isKeeper = $position === 'GK';
            $tired = $player->stamina < $threshold;
            if (!$tired && (!$policy || (!$isKeeper && $fieldRotations >= $policy['field_limit']))) {
                continue;
            }

            $starterScore = $this->readiness($player, $position);
            $starterLevel = $this->playerOverall($player, $position);
            $replacement = $subs->filter(function ($contract) use ($position, $isKeeper, $player, $starterScore, $starterLevel, $tired, $policy, $philosophy) {
                $candidate = $contract->gamePlayer;
                $naturalKeeper = $this->positionGroup($candidate->position ?? '') === 'GK';
                if ($isKeeper !== $naturalKeeper || !in_array($position, $this->playerPositionGroups($candidate), true)) {
                    return false;
                }
                if ($tired) {
                    $margin = $philosophy === TeamStyle::PHILOSOPHY_STARS ? 4 : 1;
                    return $this->readiness($candidate, $position) >= $starterScore + $margin;
                }

                return $candidate->stamina - $player->stamina >= $policy[$isKeeper ? 'keeper' : 'field']
                    && $this->playerOverall($candidate, $position) >= $starterLevel - $policy['gap'];
            })->sortByDesc(fn ($contract) => $this->readiness($contract->gamePlayer, $position))->first();

            if (!$replacement) continue;

            $this->swapStarter($team, $starter, $replacement, $state);
            $restingRoles[$player->id] = $position;
            $subs = $subs->reject(fn ($contract) => $contract->id === $replacement->id);
            if (!$tired && !$isKeeper) $fieldRotations++;
        }

        return $restingRoles;
    }

    /** Poste joué par un joueur dans la composition actuelle (GK, DEF, MID, ATT), selon son emplacement dans le schéma ; à défaut, son poste naturel. */
    private function lineupPosition(GameTeam $team, ?string $naturalPosition, int $playerId, array $slots): string
    {
        $slot = array_search($playerId, $slots, true);
        if ($slot === false) return $this->positionGroup($naturalPosition ?? '');

        return match (FormationHelper::slotRole($team->formation, (int) $slot)) {
            'GK' => 'GK',
            'DF' => 'DEF',
            'MF' => 'MID',
            'FW' => 'ATT',
            default => $this->positionGroup($naturalPosition ?? ''),
        };
    }

    /**
     * Remplace les titulaires indisponibles (blessés/suspendus).
     */
    protected function adjustTeamLineup(GameTeam $team, array $unavailableIds, GameState $state): bool
    {
        $contracts = $team->contracts->filter(fn($c) => $c->gamePlayer !== null);

        $unavailableStarters = $contracts->filter(fn($c) =>
            $c->is_starter && in_array($c->gamePlayer->id, $unavailableIds)
        );

        if ($unavailableStarters->isEmpty()) return false;

        $availableSubs = $contracts->filter(fn($c) =>
            !$c->is_starter && !in_array($c->gamePlayer->id, $unavailableIds)
        );

        if ($availableSubs->isEmpty()) return false;

        $changed = false;

        foreach ($unavailableStarters as $starterContract) {
            $starterPlayer   = $starterContract->gamePlayer;
            $slots = array_map('intval', $state->lineupSlots($team->id) ?? []);
            $starterPosition = $this->lineupPosition($team, $starterPlayer->position, (int) $starterPlayer->id, $slots);

            $replacement = $availableSubs
                ->filter(fn($c) => in_array($starterPosition, $this->playerPositionGroups($c->gamePlayer), true))
                ->sortByDesc(fn ($contract) => $this->readiness($contract->gamePlayer, $starterPosition))
                ->first();

            if (!$replacement) {
                $replacement = $availableSubs
                    ->reject(fn ($contract) => $this->positionGroup($contract->gamePlayer->position) === 'GK')
                    ->sortByDesc(fn ($contract) => $this->readiness($contract->gamePlayer, $this->positionGroup($contract->gamePlayer->position)))
                    ->first();
            }

            if (!$replacement) continue;

            $this->swapStarter($team, $starterContract, $replacement, $state);

            $availableSubs = $availableSubs->reject(fn($c) => $c->id === $replacement->id);
            $changed = true;
        }

        return $changed;
    }

    /** Valeur de jeu d'un joueur à un poste : son niveau à ce poste pondéré par son endurance, avec une petite pénalité s'il n'est pas à son poste naturel. */
    private function readiness($player, string $position): float
    {
        $stamina = (int) ($player->stamina ?? 100);
        $mastery = $this->positionGroup($player->position ?? '') === $position ? 1.0 : 0.94;

        return $this->playerOverall($player, $position) * (0.55 + 0.45 * $stamina / 100) * $mastery;
    }

    /** Échange un titulaire et un remplaçant (contrats sauvegardés) et met à jour la composition enregistrée dans l'état de la partie. */
    private function swapStarter(GameTeam $team, GameContract $starter, GameContract $replacement, GameState $state): void
    {
        $starter->is_starter = false;
        $replacement->is_starter = true;
        $starter->save();
        $replacement->save();

        $state->replaceInLineup($team->id, (int) $starter->game_player_id, (int) $replacement->game_player_id);
    }

}
