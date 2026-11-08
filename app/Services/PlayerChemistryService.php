<?php

namespace App\Services;

use App\Helpers\FormationHelper;
use App\Models\GameSaves\GameContract;
use App\Models\GameSaves\GameMatch;
use App\Models\GameSaves\GamePlayerChemistry;
use App\Models\GameSaves\GameSave;
use App\Models\GameSaves\GameTeam;
use App\Models\PlayerLink;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PlayerChemistryService
{
    private const MIN_SCORE = 60;
    private const RETRY_MATCHES = 3;
    private const LINE_RANKS = ['DF' => 0, 'MF' => 1, 'FW' => 2];

    public function recordMatch(GameSave $save, GameMatch $match, bool $simulated = false): void
    {
        DB::transaction(function () use ($save, $match, $simulated) {
            GameSave::whereKey($save->id)->lockForUpdate()->firstOrFail();
            $match->refresh();
            $stats = $match->match_stats ?? [];
            if ($match->status !== 'played' || ($stats['chemistry_recorded'] ?? false)) return;

            $maxTurns = (int) $save->getConfig('match_max_turns', 30);
            $turns = min($maxTurns, max(0, (int) ($simulated ? $maxTurns : ($stats['presence_turns'] ?? 0))));
            if ($turns === 0) return;

            foreach (['home', 'away'] as $side) {
                $lineup = $stats['lineups'][$side] ?? [];
                $roster = collect($lineup['players'] ?? [])->keyBy('id');
                if ($roster->isEmpty()) continue;
                $presence = $simulated
                    ? $this->simulatedPresence($stats, $side, $roster, $lineup['formation'] ?? null, $turns)
                    : $this->playedPresence($stats['presence'][$side] ?? [], $roster, $lineup['formation'] ?? null, $turns);
                $this->recordPairs($save, $presence, $turns);
            }

            $stats['chemistry_recorded'] = true;
            $match->match_stats = $stats;
            $match->save();
        });
    }

    private function playedPresence(mixed $segments, Collection $roster, ?string $formation, int $turns): array
    {
        $presence = [];
        $occupied = [];
        foreach (is_array($segments) ? $segments : [] as $segment) {
            if (!is_array($segment)) continue;
            $id = (int) ($segment['id'] ?? 0);
            $slot = (int) ($segment['slot'] ?? 0);
            $rank = self::LINE_RANKS[FormationHelper::slotRole($formation, $slot)] ?? null;
            if (!$roster->has($id) || $rank === null) continue;
            $from = max(0, (int) ($segment['from'] ?? 0));
            $to = min($turns, (int) ($segment['to'] ?? 0));
            for ($turn = $from; $turn < $to; $turn++) {
                if (isset($occupied[$slot][$turn]) || isset($presence[$id][$turn])) continue;
                $occupied[$slot][$turn] = true;
                $presence[$id][$turn] = $rank;
            }
        }

        return $presence;
    }

    private function simulatedPresence(array $stats, string $side, Collection $roster, ?string $formation, int $turns): array
    {
        $presence = [];
        foreach ($stats['starters'][$side] ?? [] as $id) {
            $player = $roster->get($id);
            if (!$player) continue;
            $role = isset($player['slot'])
                ? FormationHelper::slotRole($formation, (int) $player['slot'])
                : FormationHelper::roleFromPosition($player['position'] ?? null);
            $rank = self::LINE_RANKS[$role] ?? null;
            if ($rank !== null) $presence[(int) $id] = array_fill(0, $turns, $rank);
        }

        return $presence;
    }

    private function recordPairs(GameSave $save, array $presence, int $turns): void
    {
        $ids = array_keys($presence);
        sort($ids);
        $existing = $this->forPlayers($save, $ids)->keyBy(fn ($row) => "{$row->player_a_id}:{$row->player_b_id}");
        $updates = [];
        foreach ($ids as $index => $a) {
            foreach (array_slice($ids, $index + 1) as $b) {
                $weight = 0;
                $shared = 0;
                foreach (array_intersect_key($presence[$a], $presence[$b]) as $turn => $rank) {
                    $distance = abs($rank - $presence[$b][$turn]);
                    if ($distance > 1) continue;
                    $shared++;
                    $weight += $distance === 0 ? 10 : 7;
                }
                if ($shared < (int) ceil($turns / 10)) continue;

                $row = $existing->get("{$a}:{$b}");
                $updates[] = [
                    'game_save_id' => $save->id,
                    'player_a_id' => $a,
                    'player_b_id' => $b,
                    'score' => min(100, ($row->score ?? 0) + (int) round($weight / $turns)),
                    'shared_matches' => ($row->shared_matches ?? 0) + 1,
                ];
            }
        }

        if ($updates) {
            GamePlayerChemistry::upsert($updates, ['game_save_id', 'player_a_id', 'player_b_id'], ['score', 'shared_matches']);
        }
    }

    private function forPlayers(GameSave $save, array $ids): Collection
    {
        return GamePlayerChemistry::where('game_save_id', $save->id)
            ->whereIn('player_a_id', $ids)->whereIn('player_b_id', $ids)->get();
    }

    public function confirmedPartners(GameSave $save, array $ids): array
    {
        $partners = [];
        foreach ($this->forPlayers($save, $ids)->where('confirmed', true) as $row) {
            $partners[$row->player_a_id][] = ['id' => (int) $row->player_b_id, 'label' => 'Duo acquis', 'learned' => true];
            $partners[$row->player_b_id][] = ['id' => (int) $row->player_a_id, 'label' => 'Duo acquis', 'learned' => true];
        }

        return $partners;
    }

    public function summary(GameSave $save, Collection $players): array
    {
        $players = $players->keyBy('id');
        $rows = $this->forPlayers($save, $players->keys()->all())->sortByDesc('score')->sortByDesc('confirmed');
        $occupied = $this->linkedIds($save, $players->keys()->all());
        $historical = PlayerLink::partnerMapFor($players->pluck('base_player_id')->filter()->unique()->all());
        $summary = [];
        foreach ($rows as $row) {
            $a = $players[$row->player_a_id];
            $b = $players[$row->player_b_id];
            if ($this->isHistorical($historical, $a->base_player_id, $b->base_player_id)) continue;
            $retry = $row->last_attempt_at_match_count === null ? 0
                : max(0, self::RETRY_MATCHES + $row->last_attempt_at_match_count - $row->shared_matches);
            foreach ([[$a, $b], [$b, $a]] as [$player, $partner]) {
                $summary[$player->id][] = [
                    'partner_id' => $partner->id,
                    'partner_name' => trim($partner->firstname . ' ' . $partner->lastname),
                    'score' => $row->score,
                    'shared_matches' => $row->shared_matches,
                    'confirmed' => $row->confirmed,
                    'retry_matches' => $retry,
                    'chance' => $this->chance($row->score),
                    'can_attempt' => !$row->confirmed && $row->score >= self::MIN_SCORE && $retry === 0
                        && !isset($occupied[$a->id]) && !isset($occupied[$b->id]),
                ];
            }
        }

        return $summary;
    }

    public function attemptForAiTeams(GameSave $save): void
    {
        $week = (int) $save->week;
        $teams = GameTeam::where('game_save_id', $save->id)->where('is_controlled', false)
            ->where('id', '!=', (int) $save->controlled_game_team_id)
            ->with(['contracts' => fn ($query) => $query->activeAt($week)->with('gamePlayer')])->get();
        foreach ($teams as $team) {
            $players = $team->contracts->pluck('gamePlayer')->filter();
            $eligible = collect($this->summary($save, $players))->flatMap(fn ($entries, $id) =>
                collect($entries)->filter(fn ($entry) => $entry['can_attempt'] && $id < $entry['partner_id'])
                    ->map(fn ($entry) => $entry + ['player_id' => $id])
            )->sortByDesc('score')->first();
            if ($eligible) {
                $this->attempt($save, $team->id, $eligible['player_id'], $eligible['partner_id']);
            }
        }
    }

    public function attempt(GameSave $save, int $teamId, int $a, int $b): bool
    {
        [$a, $b] = $a < $b ? [$a, $b] : [$b, $a];
        $players = GameContract::where('game_save_id', $save->id)->where('game_team_id', $teamId)
            ->activeAt((int) $save->week)->whereIn('game_player_id', [$a, $b])->with('gamePlayer')
            ->get()->pluck('gamePlayer', 'game_player_id');
        if ($players->count() !== 2) $this->reject('Les deux joueurs doivent être dans le même effectif.');

        $row = $this->forPlayers($save, [$a, $b])->first();
        if (!$row || $row->score < self::MIN_SCORE || $this->linkedIds($save, [$a, $b])) {
            $this->reject('Ce duo ne peut pas encore être proposé.');
        }
        if ($row->last_attempt_at_match_count !== null
            && $row->shared_matches - $row->last_attempt_at_match_count < self::RETRY_MATCHES) {
            $this->reject('Ils doivent jouer trois nouveaux matchs ensemble avant de réessayer.');
        }

        $baseA = $players[$a]->base_player_id;
        $baseB = $players[$b]->base_player_id;
        if ($this->isHistorical(PlayerLink::partnerMapFor(array_filter([$baseA, $baseB])), $baseA, $baseB)) {
            $this->reject('Ces joueurs forment déjà un duo historique.');
        }

        $row->last_attempt_at_match_count = $row->shared_matches;
        $row->confirmed = $this->roll() <= $this->chance($row->score);
        $row->save();

        return $row->confirmed;
    }

    private function linkedIds(GameSave $save, array $ids): array
    {
        return GamePlayerChemistry::where('game_save_id', $save->id)->where('confirmed', true)
            ->where(fn ($query) => $query->whereIn('player_a_id', $ids)->orWhereIn('player_b_id', $ids))
            ->get()->flatMap(fn ($row) => [$row->player_a_id, $row->player_b_id])->flip()->all();
    }

    private function isHistorical(array $map, ?int $a, ?int $b): bool
    {
        return $a && $b && collect($map[$a] ?? [])->contains('partner_id', $b);
    }

    private function chance(int $score): int
    {
        return (int) round(40 + 35 * (max(self::MIN_SCORE, $score) - self::MIN_SCORE) / (100 - self::MIN_SCORE));
    }

    protected function roll(): int
    {
        return random_int(1, 100);
    }

    private function reject(string $message): never
    {
        throw ValidationException::withMessages(['chemistry' => $message]);
    }
}
