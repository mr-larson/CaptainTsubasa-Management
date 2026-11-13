<?php

namespace App\Services;

use App\Enums\PlayerPosition;
use App\Enums\TeamStyle;
use App\Helpers\FormationHelper;
use App\Models\GameSaves\GameInjury;
use App\Models\GameSaves\GameMatch;
use App\Models\GameSaves\GamePositionTraining;
use App\Models\GameSaves\GameSanction;
use App\Models\GameSaves\GameSave;
use App\Models\GameSaves\GameTeam;
use App\Services\Concerns\EvaluatesPlayers;
use Illuminate\Support\Collection;

/**
 * Planification des formations des équipes IA : apprentissage d'un nouveau poste ou d'un coup spécial.
 * Au plus une formation par équipe toutes les 4 semaines, selon le budget (avec réserve), l'effectif disponible, la fin de contrat, le style tactique et la philosophie.
 */
class AiFormationTrainingService
{
    use EvaluatesPlayers;

    private const COOLDOWN_WEEKS = 4;
    private const STYLES = [
        TeamStyle::TACTICAL_OFFENSIVE => ['shot', 'dribble', 'cross'],
        TeamStyle::TACTICAL_DEFENSIVE => ['block', 'tackle', 'intercept', 'hand_save', 'punch_save'],
        TeamStyle::TACTICAL_POSSESSION => ['pass', 'intercept', 'dribble'],
        TeamStyle::TACTICAL_COUNTER => ['cross', 'pass', 'shot'],
    ];
    private const ROLE_ACTIONS = [
        'GK' => ['hand_save', 'punch_save'],
        'DF' => ['block', 'intercept', 'tackle', 'pass'],
        'MF' => ['pass', 'cross', 'dribble', 'intercept', 'tackle'],
        'FW' => ['shot', 'dribble', 'cross', 'pass'],
    ];

    /** Reçoit le service qui démarre les formations et celui des cartes bonus (joueurs mis sur le banc). */
    public function __construct(
        private PositionTrainingService $formations,
        private BonusCardActivationService $cards,
    ) {}

    /**
     * Décide pour la semaine courante quelles équipes IA lancent une formation et la démarre.
     * Inactif hors phase de match, si l'entraînement IA est désactivé ou sans séance autorisée. Ignore les équipes humaines, celles en cooldown, avec moins de 13 joueurs disponibles ou en fin de saison. Enregistre la formation dans l'historique d'entraînement IA de la saison (état de la partie) et sauvegarde la partie.
     */
    public function planForWeek(GameSave $save): void
    {
        if (!$save->isInMatchPhase() || !$save->getConfig('ai_training_enabled', true)
            || (int) $save->getConfig('training_max_per_week') < 1) {
            return;
        }

        $week = (int) $save->week;
        $season = (int) $save->season;
        $humanIds = $save->controlledGameTeamIds();
        $humanIds[] = (int) $save->controlled_game_team_id;
        $fixtures = GameMatch::where('game_save_id', $save->id)->where('status', 'scheduled')
            ->where('week', '>=', $week)->get(['home_team_id', 'away_team_id', 'week']);
        $current = $fixtures->where('week', $week);
        if ($current->isEmpty()) return;

        $teams = GameTeam::where('game_save_id', $save->id)->whereNotIn('id', $humanIds)
            ->whereIn('id', $current->pluck('home_team_id')->merge($current->pluck('away_team_id'))->unique())
            ->with(['contracts' => fn ($query) => $query->activeAt($week)->with('gamePlayer')])->get();
        $blocked = GameInjury::where('game_save_id', $save->id)->where('week_return', '>', $week)->pluck('game_player_id')
            ->merge(GameSanction::where('game_save_id', $save->id)->where('week_return', '>', $week)
                ->where('weeks_suspended', '>', 0)->pluck('game_player_id'))
            ->merge(GamePositionTraining::unavailablePlayerIds($save->id, $week));
        $history = collect($save->gameState()->aiTrainingHistoryOfSeason((int) $season));
        $recent = $history->where('week', '>', $week - self::COOLDOWN_WEEKS)
            ->whereIn('stat', ['position', 'special_move'])->pluck('team_id')->flip();
        $moves = null;

        foreach ($teams as $team) {
            if ($recent->has($team->id)) continue;

            $fixturesForTeam = $fixtures->filter(fn ($m) => $m->home_team_id === $team->id || $m->away_team_id === $team->id);
            $remaining = (int) $fixturesForTeam->max('week');
            $humanWeek = (int) $fixturesForTeam->filter(fn ($m) => $m->week > $week && $m->week <= $week + 4
                && (in_array((int) $m->home_team_id, $humanIds, true) || in_array((int) $m->away_team_id, $humanIds, true)))->min('week');
            $unavailable = $blocked->merge($this->cards->getBenchedPlayerIds($save, $team->id))->flip();
            $available = $team->contracts->filter(fn ($c) => $c->gamePlayer && !$unavailable->has($c->game_player_id));
            if ($available->count() < 13 || $remaining <= $week + 1) continue;

            // Les clubs économes laissent une marge plus large pour leurs prochains achats.
            $reserve = $team->management_philosophy === TeamStyle::PHILOSOPHY_ECONOMIST ? 750 : 500;
            $spendable = (int) $team->budget - $reserve - (int) $save->getConfig('training_cost');
            $positionCost = (int) $save->getConfig('position_training_cost');
            $specialCost = (int) $save->getConfig('special_training_cost');
            if ($spendable < min($positionCost, $specialCost)) continue;

            $choice = $this->chooseFormation(
                $team, $available, $week, $remaining, $spendable >= $positionCost,
                $humanWeek,
                $humanWeek && $spendable >= $specialCost ? ($moves ??= TrainableSpecialMoves::catalog()) : [],
            );
            if (!$choice) continue;

            [$contract, $target, $move] = $choice;
            $training = $this->formations->start($save, $contract->game_player_id, $target, $move, $team);
            $history->push([
                'season' => $season, 'week' => $week, 'team_id' => $team->id, 'team_name' => $team->name,
                'player_id' => $contract->game_player_id, 'player_name' => trim($contract->gamePlayer->firstname.' '.$contract->gamePlayer->lastname),
                'stat' => $move ? 'special_move' : 'position',
                'formation_label' => $move['label'] ?? PlayerPosition::labels()[$target],
                'cost' => $training->cost, 'week_return' => $training->week_return,
            ]);
        }

        if ($history->count() > count($save->gameState()->aiTrainingHistoryOfSeason((int) $season))) {
            $save->gameState()->putAiTrainingHistory($history->values()->all());
            $save->save();
        }
    }

    /**
     * Choisit la meilleure formation pour une équipe : tableau [contrat, poste cible, coup spécial] ou null.
     * Évalue deux types de candidats : un remplaçant en surnombre qui apprend un poste en manque dans le schéma tactique, ou un joueur qui apprend un coup spécial cohérent avec son rôle, son niveau et le style de l'équipe. Retient le meilleur score.
     */
    private function chooseFormation(GameTeam $team, Collection $available, int $week, int $lastWeek, bool $canTrainPosition, int $humanWeek, array $moves): ?array
    {
        $required = array_count_values(array_map(fn ($slot) => FormationHelper::slotRole($team->formation, $slot), range(1, 11)));
        $natural = $available->countBy(fn ($c) => FormationHelper::roleFromPosition($c->gamePlayer->position));
        $coverage = $available->flatMap(fn ($c) => $this->playerPositionGroups($c->gamePlayer))->countBy();
        $style = self::STYLES[$team->tactical_style] ?? [];
        $stars = $team->management_philosophy === TeamStyle::PHILOSOPHY_STARS;
        $candidates = [];

        foreach ($available as $contract) {
            $p = $contract->gamePlayer;
            $role = FormationHelper::roleFromPosition($p->position);
            if (!$role || $natural->get($role, 0) <= $required[$role] || $p->stamina < 45) continue;

            $knownPositions = [$p->position, ...($p->secondary_positions ?? [])];
            $endWeek = min($lastWeek, (int) ($contract->end_week ?? $lastWeek));
            if ($canTrainPosition && !$contract->is_starter && $role !== 'GK') {
                foreach ([PlayerPosition::Defender, PlayerPosition::Midfielder, PlayerPosition::Forward] as $position) {
                    $targetRole = FormationHelper::roleFromPosition($position->value);
                    $targetGroup = $this->positionGroup($position->value);
                    $shortage = $required[$targetRole] - $coverage->get($targetGroup, 0);
                    if (in_array($position->value, $knownPositions, true) || $shortage < 0) continue;

                    $weeks = PositionTrainingService::durationFor($knownPositions, $position->value);
                    $coversAbsence = $available->filter(fn ($backup) => $backup->id !== $contract->id
                        && FormationHelper::roleFromPosition($backup->gamePlayer->position) === $role
                        && ($backup->end_week === null || $backup->end_week >= $week + $weeks - 1))->count();
                    if ($endWeek < $week + $weeks + 1 || $coversAbsence < $required[$role]) continue;

                    $score = 42 + $shortage * 6 - $weeks * 4 + $this->playerOverall($p, $targetGroup) / 3;
                    if ($team->management_philosophy === TeamStyle::PHILOSOPHY_COLLECTIVE) $score += 8;
                    $candidates[] = [$score, $contract, $position->value, null];
                }
            }

            if ($endWeek < max($week + 3, $humanWeek)) continue;
            $knownActions = array_map(TrainableSpecialMoves::actionOf(...), $p->special_moves ?? []);
            foreach ($moves as $move) {
                $action = TrainableSpecialMoves::actionOf($move);
                if (!in_array($action, self::ROLE_ACTIONS[$role], true) || in_array($action, $knownActions, true)) continue;
                $ability = $action === 'cross' ? 0.7 * $p->pass + 0.3 * $p->attack : (int) $p->{$action};
                $fitsStyle = in_array($action, $style, true);
                if ($ability < ($fitsStyle ? 30 : 40)) continue;

                $score = $ability + ($fitsStyle ? 10 : 0) + ($contract->is_starter ? ($stars ? 9 : 3) : 0);
                $candidates[] = [$score, $contract, $move['key'], $move];
            }
        }

        $best = collect($candidates)->sortByDesc(0)->first();
        return $best ? array_slice($best, 1) : null;
    }
}
