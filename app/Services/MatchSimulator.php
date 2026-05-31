<?php

namespace App\Services;

use App\Models\GameSaves\GameMatch;
use App\Models\GameSaves\GameTeam;
use Illuminate\Support\Collection;

class MatchSimulator
{
    public function simulateOtherMatchesOfWeek(GameMatch $playedMatch): void
    {
        $others = GameMatch::query()
            ->where('game_save_id', $playedMatch->game_save_id)
            ->where('week', $playedMatch->week)
            ->where('status', 'scheduled')
            ->where('id', '!=', $playedMatch->id)
            ->with(['homeTeam.contracts.gamePlayer', 'awayTeam.contracts.gamePlayer'])
            ->get();

        foreach ($others as $m) {
            $this->simulateAndSave($m);
        }
    }

    public function simulateMatchesCollection(Collection $matches): void
    {
        $matches->loadMissing(['homeTeam.contracts.gamePlayer', 'awayTeam.contracts.gamePlayer']);

        foreach ($matches as $m) {
            if ($m->status !== 'scheduled') continue;
            $this->simulateAndSave($m);
        }
    }

    // ==========================
    //   SIMULATION PRINCIPALE
    // ==========================

    private function simulateAndSave(GameMatch $m): void
    {
        [$homeScore, $awayScore, $matchStats] = $this->simulateMatch(
            $m->homeTeam,
            $m->awayTeam
        );

        $m->home_score  = $homeScore;
        $m->away_score  = $awayScore;
        $m->status      = 'played';
        $m->match_stats = $matchStats;
        $m->save();

        $this->applyResultToStandings($m);
    }

    /**
     * Simule un match complet et retourne [homeScore, awayScore, matchStats].
     * matchStats est au même format que celui produit par engine.js côté client.
     */
    private function simulateMatch(GameTeam $home, GameTeam $away): array
    {
        $homePlayers = $this->getStarters($home);
        $awayPlayers = $this->getStarters($away);

        $homeRatings = $this->teamRatings($homePlayers);
        $awayRatings = $this->teamRatings($awayPlayers);

        // Stats par joueur (indexées par game_player_id)
        $playerStats = [];
        $teamStats   = [
            'home' => ['goals' => 0, 'shots' => 0, 'passes' => 0, 'dribbles' => 0, 'saves' => 0, 'duelsWon' => 0, 'duelsLost' => 0],
            'away' => ['goals' => 0, 'shots' => 0, 'passes' => 0, 'dribbles' => 0, 'saves' => 0, 'duelsWon' => 0, 'duelsLost' => 0],
        ];

        $homeGoals = 0;
        $awayGoals = 0;

        // Chances pour chaque équipe
        $homeChances = 8 + random_int(-2, 2);
        $awayChances = 7 + random_int(-2, 2);

        // Simuler les chances HOME
        for ($i = 0; $i < max(1, $homeChances); $i++) {
            $attacker  = $this->randomAttacker($homePlayers);
            $defender  = $this->randomDefender($awayPlayers);
            $gk        = $this->getGoalkeeper($awayPlayers);
            $action    = $this->randomOffenseAction();

            $isShot      = $action === 'shot';
            $attackRoll  = $homeRatings['att'] + $this->d20() + 2; // avantage domicile
            $defenseRoll = $isShot
                ? $awayRatings['gk']  + $this->d20()
                : $awayRatings['def'] + $this->d20();
            $success  = $attackRoll > $defenseRoll;
            $isGoal   = $success && $isShot;

            // Stats attaquant
            if ($attacker) {
                $pid = (string) $attacker->id;
                $playerStats[$pid] = $playerStats[$pid] ?? $this->emptyPlayerStats();
                $playerStats[$pid]['offense'][$action]['attempts']++;
                if ($success) $playerStats[$pid]['offense'][$action]['success']++;
                if ($isGoal)  $playerStats[$pid]['offense']['goals'] = ($playerStats[$pid]['offense']['goals'] ?? 0) + 1;
                $success ? $playerStats[$pid]['duelsWon']++ : $playerStats[$pid]['duelsLost']++;
            }

            // Stats défenseur
            $defAction = $this->randomDefenseAction();
            if ($defender) {
                $pid = (string) $defender->id;
                $playerStats[$pid] = $playerStats[$pid] ?? $this->emptyPlayerStats();
                $playerStats[$pid]['defense'][$defAction]['attempts']++;
                if (!$success) $playerStats[$pid]['defense'][$defAction]['success']++;
                $success ? $playerStats[$pid]['duelsLost']++ : $playerStats[$pid]['duelsWon']++;
            }

            // Stats gardien si tir
            if ($action === 'shot' && $gk) {
                $pid = (string) $gk->id;
                $playerStats[$pid] = $playerStats[$pid] ?? $this->emptyPlayerStats();
                $gkAction = random_int(0, 1) ? 'hands' : 'punch';
                $playerStats[$pid]['defense'][$gkAction]['attempts']++;
                $teamStats['away']['saves'] = ($teamStats['away']['saves'] ?? 0) + ($success ? 0 : 1);
                if (!$success) $playerStats[$pid]['defense'][$gkAction]['success']++;
                $success ? $playerStats[$pid]['duelsLost']++ : $playerStats[$pid]['duelsWon']++;
            }

            // Stats équipe
            if ($action === 'pass')         $teamStats['home']['passes']++;
            elseif ($action === 'dribble')  $teamStats['home']['dribbles']++;
            else                            $teamStats['home']['shots']++;
            if ($success) {
                $teamStats['home']['duelsWon']++;
                $teamStats['away']['duelsLost']++;
                if ($action === 'shot') {
                    $homeGoals++;
                    $teamStats['home']['goals']++;
                }
            } else {
                $teamStats['home']['duelsLost']++;
                $teamStats['away']['duelsWon']++;
            }
        }

        // Simuler les chances AWAY
        for ($i = 0; $i < max(1, $awayChances); $i++) {
            $attacker  = $this->randomAttacker($awayPlayers);
            $defender  = $this->randomDefender($homePlayers);
            $gk        = $this->getGoalkeeper($homePlayers);
            $action    = $this->randomOffenseAction();

            $isShot      = $action === 'shot';
            $attackRoll  = $awayRatings['att'] + $this->d20();
            $defenseRoll = $isShot
                ? $homeRatings['gk']  + $this->d20()
                : $homeRatings['def'] + $this->d20();
            $success  = $attackRoll > $defenseRoll;
            $isGoal   = $success && $isShot;

            if ($attacker) {
                $pid = (string) $attacker->id;
                $playerStats[$pid] = $playerStats[$pid] ?? $this->emptyPlayerStats();
                $playerStats[$pid]['offense'][$action]['attempts']++;
                if ($success) $playerStats[$pid]['offense'][$action]['success']++;
                if ($isGoal)  $playerStats[$pid]['offense']['goals'] = ($playerStats[$pid]['offense']['goals'] ?? 0) + 1;
                $success ? $playerStats[$pid]['duelsWon']++ : $playerStats[$pid]['duelsLost']++;
            }

            $defAction = $this->randomDefenseAction();
            if ($defender) {
                $pid = (string) $defender->id;
                $playerStats[$pid] = $playerStats[$pid] ?? $this->emptyPlayerStats();
                $playerStats[$pid]['defense'][$defAction]['attempts']++;
                if (!$success) $playerStats[$pid]['defense'][$defAction]['success']++;
                $success ? $playerStats[$pid]['duelsLost']++ : $playerStats[$pid]['duelsWon']++;
            }

            if ($action === 'shot' && $gk) {
                $pid = (string) $gk->id;
                $playerStats[$pid] = $playerStats[$pid] ?? $this->emptyPlayerStats();
                $gkAction = random_int(0, 1) ? 'hands' : 'punch';
                $playerStats[$pid]['defense'][$gkAction]['attempts']++;
                $teamStats['home']['saves'] = ($teamStats['home']['saves'] ?? 0) + ($success ? 0 : 1);
                if (!$success) $playerStats[$pid]['defense'][$gkAction]['success']++;
                $success ? $playerStats[$pid]['duelsLost']++ : $playerStats[$pid]['duelsWon']++;
            }

            if ($action === 'pass')         $teamStats['away']['passes']++;
            elseif ($action === 'dribble')  $teamStats['away']['dribbles']++;
            else                            $teamStats['away']['shots']++;
            if ($success) {
                $teamStats['away']['duelsWon']++;
                $teamStats['home']['duelsLost']++;
                if ($action === 'shot') {
                    $awayGoals++;
                    $teamStats['away']['goals']++;
                }
            } else {
                $teamStats['away']['duelsLost']++;
                $teamStats['home']['duelsWon']++;
            }
        }

        // Clamp scores
        $homeGoals = min($homeGoals, 6);
        $awayGoals = min($awayGoals, 6);
        $teamStats['home']['goals'] = $homeGoals;
        $teamStats['away']['goals'] = $awayGoals;

        // Cohérence playerStats goals vs teamStats goals
        // Si teamStats a des buts mais playerStats n'en a pas (attaquant null),
        // attribuer les buts au meilleur attaquant disponible
        foreach (['home' => $homePlayers, 'away' => $awayPlayers] as $side => $sidePlayers) {
            $teamGoals = $teamStats[$side]['goals'] ?? 0;
            if ($teamGoals <= 0) continue;

            // Compter uniquement les buts des joueurs de CETTE équipe
            $sidePlayerIds = $sidePlayers->map(fn($p) => (string) $p->id)->toArray();
            $playerGoals   = array_sum(array_map(
                fn($pid) => $playerStats[$pid]['offense']['goals'] ?? 0,
                array_filter($sidePlayerIds, fn($pid) => isset($playerStats[$pid]))
            ));

            if ($playerGoals < $teamGoals) {
                $bestAttacker = $sidePlayers
                    ->filter(fn($p) => isset($playerStats[(string) $p->id]))
                    ->sortByDesc(fn($p) => ($p->shot ?? 0) + ($p->attack ?? 0))
                    ->first();

                if ($bestAttacker) {
                    $pid     = (string) $bestAttacker->id;
                    $missing = $teamGoals - $playerGoals;
                    $playerStats[$pid]['offense']['goals'] =
                        ($playerStats[$pid]['offense']['goals'] ?? 0) + $missing;
                }
            }
        }

        $matchStats = [
            'teams'   => $teamStats,
            'players' => $playerStats,
        ];

        return [$homeGoals, $awayGoals, $matchStats];
    }

    // ==========================
    //   HELPERS JOUEURS
    // ==========================

    private function getStarters(GameTeam $team): Collection
    {
        return $team->contracts
            ->filter(fn($c) => $c->is_starter && $c->gamePlayer)
            ->map(fn($c) => $c->gamePlayer)
            ->values()
            ->take(11);
    }

    private function teamRatings(Collection $players): array
    {
        if ($players->isEmpty()) {
            return ['att' => 40, 'def' => 40, 'gk' => 40];
        }

        $att = (int) round($players->avg(fn($p) =>
                ($p->attack ?? 0) + ($p->shot ?? 0) + ($p->dribble ?? 0) + ($p->pass ?? 0)
            ) / 4);

        $def = (int) round($players->avg(fn($p) =>
                ($p->defense ?? 0) + ($p->tackle ?? 0) + ($p->intercept ?? 0) + ($p->block ?? 0)
            ) / 4);

        $gkBest = $players->max(fn($p) => ($p->hand_save ?? 0) + ($p->punch_save ?? 0));
        $gk     = (int) max(20, round(($gkBest ?: 0) / 2));

        return ['att' => $att, 'def' => $def, 'gk' => $gk];
    }

    private function randomAttacker(Collection $players)
    {
        // Préfère les joueurs avec attack/shot élevés
        $attackers = $players->filter(fn($p) => ($p->attack ?? 0) > 30 || ($p->shot ?? 0) > 30);
        $pool = $attackers->isNotEmpty() ? $attackers : $players;
        return $pool->isEmpty() ? null : $pool->random();
    }

    private function randomDefender(Collection $players)
    {
        $defenders = $players->filter(fn($p) => ($p->defense ?? 0) > 30 || ($p->tackle ?? 0) > 30);
        $pool = $defenders->isNotEmpty() ? $defenders : $players;
        return $pool->isEmpty() ? null : $pool->random();
    }

    private function getGoalkeeper(Collection $players)
    {
        // Le gardien = joueur avec le plus de hand_save + punch_save
        return $players->sortByDesc(fn($p) => ($p->hand_save ?? 0) + ($p->punch_save ?? 0))->first();
    }

    private function randomOffenseAction(): string
    {
        // Distribution réaliste : beaucoup de passes, dribbles, quelques tirs
        $roll = random_int(1, 10);
        if ($roll <= 4) return 'pass';
        if ($roll <= 7) return 'dribble';
        return 'shot';
    }

    private function randomDefenseAction(): string
    {
        $roll = random_int(1, 6);
        if ($roll <= 2) return 'intercept';
        if ($roll <= 4) return 'tackle';
        return 'block';
    }

    private function d20(): int
    {
        return random_int(1, 20);
    }

    // ==========================
    //   STANDINGS
    // ==========================

    private function applyResultToStandings(GameMatch $m): void
    {
        if ($m->status !== 'played') return;
        if ($m->home_score === null || $m->away_score === null) return;

        $home = $m->relationLoaded('homeTeam') ? $m->homeTeam : $m->homeTeam()->first();
        $away = $m->relationLoaded('awayTeam') ? $m->awayTeam : $m->awayTeam()->first();

        if (!$home || !$away) return;

        if ($m->home_score > $m->away_score) {
            $home->wins   = ($home->wins   ?? 0) + 1;
            $away->losses = ($away->losses ?? 0) + 1;
        } elseif ($m->home_score < $m->away_score) {
            $away->wins   = ($away->wins   ?? 0) + 1;
            $home->losses = ($home->losses ?? 0) + 1;
        } else {
            $home->draws  = ($home->draws  ?? 0) + 1;
            $away->draws  = ($away->draws  ?? 0) + 1;
        }

        $home->save();
        $away->save();
    }

    // ==========================
    //   STATS VIDES
    // ==========================

    private function emptyPlayerStats(): array
    {
        return [
            'offense' => [
                'pass'    => ['attempts' => 0, 'success' => 0],
                'shot'    => ['attempts' => 0, 'success' => 0],
                'dribble' => ['attempts' => 0, 'success' => 0],
                'special' => ['attempts' => 0, 'success' => 0],
                'goals'   => 0,
            ],
            'defense' => [
                'intercept' => ['attempts' => 0, 'success' => 0],
                'tackle'    => ['attempts' => 0, 'success' => 0],
                'block'     => ['attempts' => 0, 'success' => 0],
                'hands'     => ['attempts' => 0, 'success' => 0],
                'punch'     => ['attempts' => 0, 'success' => 0],
                'gkSpecial' => ['attempts' => 0, 'success' => 0],
            ],
            'duelsWon'  => 0,
            'duelsLost' => 0,
        ];
    }
}
