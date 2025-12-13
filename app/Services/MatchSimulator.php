<?php

namespace App\Services;

use App\Models\GameMatch;
use App\Models\GameTeam;
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
            ->with(['homeTeam', 'awayTeam', 'homeTeam.contracts.gamePlayer', 'awayTeam.contracts.gamePlayer'])
            ->get();

        foreach ($others as $m) {
            [$hs, $as] = $this->simulateScore($m->homeTeam, $m->awayTeam);

            $m->home_score = $hs;
            $m->away_score = $as;
            $m->status     = 'played';
            $m->save();

            $this->applyResultToStandings($m);
        }
    }

    private function simulateScore(GameTeam $home, GameTeam $away): array
    {
        // ratings
        $homeR = $this->teamRatings($home);
        $awayR = $this->teamRatings($away);

        // occasions (MVP)
        $homeChances = 8 + random_int(-2, 2);
        $awayChances = 7 + random_int(-2, 2);

        $homeGoals = $this->goalsFromChances($homeR, $awayR, $homeChances, true);
        $awayGoals = $this->goalsFromChances($awayR, $homeR, $awayChances, false);

        return [$homeGoals, $awayGoals];
    }

    private function teamRatings(GameTeam $team): array
    {
        // on prend les 11 premiers joueurs sous contrat (MVP)
        $players = $team->contracts
            ->map(fn($c) => $c->gamePlayer)
            ->filter()
            ->values()
            ->take(11);

        if ($players->isEmpty()) {
            return ['att' => 40, 'def' => 40, 'gk' => 40];
        }

        $att = (int) round($players->avg(fn($p) =>
                ($p->attack ?? 0) + ($p->shot ?? 0) + ($p->dribble ?? 0) + ($p->pass ?? 0)
            ) / 4);

        $def = (int) round($players->avg(fn($p) =>
                ($p->defense ?? 0) + ($p->tackle ?? 0) + ($p->intercept ?? 0) + ($p->block ?? 0)
            ) / 4);

        // gardien = meilleur GK trouvé (ou fallback)
        $gkBest = $players->max(fn($p) => (($p->hand_save ?? 0) + ($p->punch_save ?? 0)));
        $gk     = (int) max(20, round(($gkBest ?: 0) / 2));

        return ['att' => $att, 'def' => $def, 'gk' => $gk];
    }

    private function goalsFromChances(array $atk, array $def, int $chances, bool $isHome): int
    {
        $goals = 0;

        for ($i = 0; $i < max(1, $chances); $i++) {
            $attackRoll  = $atk['att'] + $this->d20() + ($isHome ? 2 : 0);
            $defenseRoll = $def['def'] + (int) round($def['gk'] * 0.5) + $this->d20();

            // seuil MVP
            if ($attackRoll > $defenseRoll + 2) {
                $goals++;
            }
        }

        // petit clamp MVP (évite les 9-8 trop souvent)
        return min($goals, 6);
    }

    private function d20(): int
    {
        return random_int(1, 20);
    }
    public function simulateMatchesCollection(Collection $matches): void
    {
        // On suppose que la collection contient des GameMatch
        // et qu'on peut charger les relations si pas déjà fait.
        $matches->loadMissing(['homeTeam.contracts.gamePlayer', 'awayTeam.contracts.gamePlayer']);

        foreach ($matches as $m) {
            if ($m->status !== 'scheduled') {
                continue;
            }

            [$hs, $as] = $this->simulateScore($m->homeTeam, $m->awayTeam);

            $m->home_score = $hs;
            $m->away_score = $as;
            $m->status     = 'played';
            $m->save();

            // Ici homeTeam/awayTeam sont déjà en mémoire
            $this->applyResultToStandings($m);
        }
    }

    private function applyResultToStandings(GameMatch $m): void
    {
        // sécurité
        if ($m->status !== 'played') return;
        if ($m->home_score === null || $m->away_score === null) return;

        // on part des relations déjà chargées si dispo
        $home = $m->relationLoaded('homeTeam') ? $m->homeTeam : $m->homeTeam()->first();
        $away = $m->relationLoaded('awayTeam') ? $m->awayTeam : $m->awayTeam()->first();

        if (!$home || !$away) return;

        if ($m->home_score > $m->away_score) {
            $home->wins   = ($home->wins ?? 0) + 1;
            $away->losses = ($away->losses ?? 0) + 1;
        } elseif ($m->home_score < $m->away_score) {
            $away->wins   = ($away->wins ?? 0) + 1;
            $home->losses = ($home->losses ?? 0) + 1;
        } else {
            $home->draws  = ($home->draws ?? 0) + 1;
            $away->draws  = ($away->draws ?? 0) + 1;
        }

        $home->save();
        $away->save();
    }

}
