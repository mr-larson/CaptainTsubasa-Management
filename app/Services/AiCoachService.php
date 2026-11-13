<?php

namespace App\Services;

use App\Enums\PromiseStatus;
use App\Models\GameSaves\GameDeclaration;
use App\Models\GameSaves\GameInjury;
use App\Models\GameSaves\GameMatch;
use App\Models\GameSaves\GamePositionTraining;
use App\Models\GameSaves\GamePromise;
use App\Models\GameSaves\GameSanction;
use App\Models\GameSaves\GameSave;
use App\Models\GameSaves\GameTeam;

class AiCoachService
{
    private const PROMISE_COOLDOWN_WEEKS = 5;

    public function __construct(
        private DeclarationService $declarations,
        private PromiseService $promises,
        private BonusCardActivationService $cards,
    ) {}

    public function prepareForWeek(GameSave $save, int $playedWeek): void
    {
        $week = (int) $save->week;
        $season = (int) $save->season;
        $fixtures = GameMatch::where('game_save_id', $save->id)
            ->where('week', $week)->where('status', 'scheduled')
            ->get(['id', 'home_team_id', 'away_team_id']);
        $played = GameMatch::where('game_save_id', $save->id)
            ->where('week', $playedWeek)->where('status', 'played')->orderBy('id')
            ->with('playerStats')
            ->get(['id', 'game_save_id', 'home_team_id', 'away_team_id', 'match_stats']);

        $aiTeams = GameTeam::where('game_save_id', $save->id)
            ->whereIn('id', $fixtures->pluck('home_team_id')->merge($fixtures->pluck('away_team_id'))->unique())
            ->whereNotIn('id', $save->controlledGameTeamIds())
            ->where('id', '!=', $save->controlled_game_team_id ?? 0)
            ->with(['contracts' => fn ($query) => $query->activeAt($week)->with('gamePlayer')])
            ->get();
        if ($aiTeams->isEmpty() || $played->isEmpty()) return;

        $unavailable = GameInjury::where('game_save_id', $save->id)->where('week_return', '>', $week)->pluck('game_player_id')
            ->merge(GameSanction::where('game_save_id', $save->id)->where('week_return', '>', $week)
                ->where('weeks_suspended', '>', 0)->pluck('game_player_id'))
            ->merge(GamePositionTraining::unavailablePlayerIds($save->id, $week));

        foreach ($aiTeams as $team) {
            $match = $played->first(fn ($match) => $match->home_team_id === $team->id || $match->away_team_id === $team->id);
            if (!$match) continue;

            $blocked = $unavailable->merge($this->cards->getBenchedPlayerIds($save, $team->id))->flip();
            $starters = $team->contracts->filter(fn ($contract) =>
                $contract->is_starter && $contract->gamePlayer && !$blocked->has($contract->game_player_id)
            )->pluck('gamePlayer');
            $stats = $match->playerStatsMap();

            $recentDeclaration = GameDeclaration::where('game_save_id', $save->id)->where('season', $season)
                ->whereIn('game_player_id', $team->contracts->pluck('game_player_id'))
                ->where('week', '>', $week - DeclarationService::COOLDOWN_WEEKS)
                ->exists();
            if (!$recentDeclaration) {
                $balance = fn ($player) => (int) ($stats[$player->id]['duelsWon'] ?? 0)
                    - (int) ($stats[$player->id]['duelsLost'] ?? 0);
                $inForm = $starters->filter(fn ($player) => $balance($player) > 0)->sortByDesc($balance)->first();
                $struggling = $starters->filter(fn ($player) => $balance($player) < 0 && $player->morale >= 60)
                    ->sortBy($balance)->first();
                if ($inForm) {
                    $this->declarations->declare($save, $inForm, 'praise', $team->id);
                } elseif ($struggling) {
                    $this->declarations->declare($save, $struggling, 'criticize', $team->id);
                }
            }

            $hasPromise = GamePromise::where('game_save_id', $save->id)->where('game_team_id', $team->id)
                ->where('season', $season)
                ->where(fn ($query) => $query->where('status', PromiseStatus::Pending)
                    ->orWhere('start_week', '>', $week - self::PROMISE_COOLDOWN_WEEKS))
                ->exists();
            if ($hasPromise) continue;

            $playedIds = MoraleService::resolvePlayedPlayerIds($match);
            $targetTurns = PromiseService::playingTimeTarget($save);
            $candidate = $starters->filter(fn ($player) =>
                $player->stamina >= 50 && $player->morale <= 70
                && ($player->morale < 55
                    || ($match->match_stats['playtime'][$player->id] ?? (isset($playedIds[$player->id]) ? $targetTurns : 0)) < $targetTurns)
            )->sortBy('morale')->first();

            if ($candidate) {
                $this->promises->create($save, $candidate, $team->id, 'playing_time');
            }
        }
    }
}
