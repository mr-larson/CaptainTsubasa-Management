<?php

namespace App\Services;

use App\Helpers\FormationHelper;
use App\Models\GameSaves\GameContract;
use App\Models\GameSaves\GameMatch;
use App\Models\GameSaves\GamePlayer;
use App\Models\GameSaves\GamePlayerChemistry;
use App\Models\GameSaves\GameSave;
use App\Models\GameSaves\GameTeam;
use App\Models\PlayerLink;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Alchimie entre coéquipiers : plus deux joueurs évoluent ensemble (à des postes voisins), plus leur score de duo monte (0-100).
 * À partir de 60, on peut tenter de « confirmer » le duo (chance de 40 % à 75 % selon le score) ; un duo confirmé est un « duo acquis ».
 * Un joueur ne peut appartenir qu'à un seul duo confirmé, et les duos déjà historiques (PlayerLink) sont exclus.
 * Après un échec, il faut jouer 3 nouveaux matchs ensemble avant de réessayer.
 */
class PlayerChemistryService
{
    /** Score minimum pour pouvoir tenter la confirmation d'un duo. */
    private const MIN_SCORE = 60;
    /** Nombre de matchs communs à jouer avant de retenter après un échec. */
    private const RETRY_MATCHES = 3;
    /** Rang de chaque ligne (défense, milieu, attaque) : deux joueurs ne partagent de l'alchimie que si leurs lignes sont identiques ou adjacentes. */
    private const LINE_RANKS = ['DF' => 0, 'MF' => 1, 'FW' => 2];

    /**
     * Enregistre l'alchimie d'un match joué : pour chaque équipe, calcule la présence de chaque joueur tour par tour puis met à jour les paires.
     * Pose elle-même un verrou sur la sauvegarde dans une transaction ; idempotent grâce au drapeau match_stats.chemistry_recorded.
     * $simulated = match simulé par l'IA (les titulaires sont supposés présents toute la durée).
     */
    public function recordMatch(GameSave $save, GameMatch $match, bool $simulated = false): void
    {
        DB::transaction(function () use ($save, $match, $simulated) {
            GameSave::whereKey($save->id)->lockForUpdate()->firstOrFail();
            $match->refresh();
            $stats = $match->match_stats ?? [];
            if ($match->status !== \App\Enums\MatchStatus::Played || ($stats['chemistry_recorded'] ?? false)) return;

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

    /**
     * Présence d'un match joué par l'humain : à partir des segments (joueur, poste, tour de début/fin), renvoie pour chaque joueur la ligne occupée à chaque tour [id][tour] = rang.
     * Ignore les joueurs hors effectif et un poste ou un joueur déjà compté sur le même tour.
     */
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

    /** Présence d'un match simulé : chaque titulaire est réputé présent à son poste sur tous les tours. */
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

    /**
     * Pour chaque paire de joueurs, compte les tours passés dans la même ligne (poids 10) ou une ligne adjacente (poids 7), puis ajoute au score de duo (plafonné à 100) et +1 match commun.
     * Les paires qui ont partagé moins d'un dixième des tours sont ignorées. Écrit en base par upsert.
     */
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

    /** Lignes d'alchimie dont les deux joueurs font partie de la liste d'ids. */
    private function forPlayers(GameSave $save, array $ids): Collection
    {
        return GamePlayerChemistry::where('game_save_id', $save->id)
            ->whereIn('player_a_id', $ids)->whereIn('player_b_id', $ids)->get();
    }

    /** Duos confirmés par joueur ([id joueur] => liste de partenaires, libellé « Duo acquis »), pour les ids donnés. */
    public function confirmedPartners(GameSave $save, array $ids): array
    {
        $partners = [];
        foreach ($this->forPlayers($save, $ids)->where('confirmed', true) as $row) {
            $partners[$row->player_a_id][] = ['id' => (int) $row->player_b_id, 'label' => 'Duo acquis', 'learned' => true];
            $partners[$row->player_b_id][] = ['id' => (int) $row->player_a_id, 'label' => 'Duo acquis', 'learned' => true];
        }

        return $partners;
    }

    /**
     * Résumé des duos pour l'affichage, indexé par joueur : partenaire, score, matchs communs, confirmé ou non, matchs restants avant nouvelle tentative, chance de réussite et possibilité de tenter.
     * Exclut les duos historiques et, si $teamByPlayer est fourni, les paires de deux équipes différentes.
     */
    public function summary(GameSave $save, Collection $players, ?Collection $teamByPlayer = null): array
    {
        $players = $players->keyBy('id');

        return $this->buildSummary(
            $players,
            $this->forPlayers($save, $players->keys()->all()),
            $this->linkedIds($save, $players->keys()->all()),
            PlayerLink::partnerMapFor($players->pluck('base_player_id')->filter()->unique()->all()),
            $teamByPlayer,
        );
    }

    /**
     * Construit le résumé à partir de lectures DÉJÀ FAITES (summary() les fait pour un
     * groupe de joueurs ; attemptForAiTeams() les fait une seule fois pour toute la ligue).
     *
     * @param Collection $players    joueurs concernés, indexés par id
     * @param Collection $rows       lignes d'alchimie dont les deux joueurs sont dans $players
     * @param array      $occupied   ids (en clés) des joueurs déjà engagés dans un duo confirmé
     * @param array      $historical duos historiques (cf. PlayerLink::partnerMapFor)
     */
    private function buildSummary(Collection $players, Collection $rows, array $occupied, array $historical, ?Collection $teamByPlayer): array
    {
        $rows = $rows->sortByDesc('score')->sortByDesc('confirmed');
        $summary = [];
        foreach ($rows as $row) {
            if ($teamByPlayer !== null && $teamByPlayer[$row->player_a_id] !== $teamByPlayer[$row->player_b_id]) {
                continue;
            }

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

    /**
     * Décision d'IA : chaque équipe non pilotée par l'humain tente son meilleur duo éligible
     * (score le plus élevé).
     *
     * Les lectures (alchimies, duos engagés, duos historiques) sont faites UNE FOIS pour tous
     * les joueurs des équipes IA, puis réparties par équipe en mémoire : pas de relecture par équipe.
     */
    public function attemptForAiTeams(GameSave $save): void
    {
        $week = (int) $save->week;
        $teams = GameTeam::where('game_save_id', $save->id)->where('is_controlled', false)
            ->where('id', '!=', (int) $save->controlled_game_team_id)
            ->with(['contracts' => fn ($query) => $query->activeAt($week)->with('gamePlayer')])->get();

        $playersByTeam = $teams->mapWithKeys(fn ($team) => [$team->id => $team->contracts->pluck('gamePlayer')->filter()->keyBy('id')]);
        $allPlayers    = $playersByTeam->flatten(1)->keyBy('id');
        $allIds        = $allPlayers->keys()->all();
        if ($allIds === []) {
            return;
        }

        // Lectures globales : une paire n'est jamais entre deux équipes différentes ici (on filtre par équipe ci-dessous).
        $allRows    = $this->forPlayers($save, $allIds);
        $occupied   = $this->linkedIds($save, $allIds);
        $historical = PlayerLink::partnerMapFor($allPlayers->pluck('base_player_id')->filter()->unique()->all());

        foreach ($teams as $team) {
            $players = $playersByTeam[$team->id];
            $rows    = $allRows->filter(fn ($row) => $players->has($row->player_a_id) && $players->has($row->player_b_id));

            $eligible = collect($this->buildSummary($players, $rows, $occupied, $historical, null))->flatMap(fn ($entries, $id) =>
                collect($entries)->filter(fn ($entry) => $entry['can_attempt'] && $id < $entry['partner_id'])
                    ->map(fn ($entry) => $entry + ['player_id' => $id])
            )->sortByDesc('score')->first();
            if ($eligible) {
                // Les lectures de la ligue sont déjà là : pas de relecture pour cette tentative.
                [$a, $b] = [$eligible['player_id'], $eligible['partner_id']]; // déjà ordonnés : a < b
                $this->confirmAttempt(
                    $rows->first(fn ($row) => $row->player_a_id === $a && $row->player_b_id === $b),
                    $players[$a],
                    $players[$b],
                    fn () => isset($occupied[$a]) || isset($occupied[$b]),
                    fn () => $historical,
                );
            }
        }
    }

    /**
     * Tente de confirmer le duo $a/$b d'une équipe : vérifie effectif, score minimum, duos déjà liés, délai de réessai et duo historique (sinon ValidationException).
     * Tire un dé contre la chance, enregistre la tentative et le résultat ; renvoie true si le duo est confirmé.
     */
    public function attempt(GameSave $save, int $teamId, int $a, int $b): bool
    {
        [$a, $b] = $a < $b ? [$a, $b] : [$b, $a];
        $players = GameContract::where('game_save_id', $save->id)->where('game_team_id', $teamId)
            ->activeAt((int) $save->week)->whereIn('game_player_id', [$a, $b])->with('gamePlayer')
            ->get()->pluck('gamePlayer', 'game_player_id');
        if ($players->count() !== 2) $this->reject('Les deux joueurs doivent être dans le même effectif.');

        return $this->confirmAttempt(
            $this->forPlayers($save, [$a, $b])->first(),
            $players[$a],
            $players[$b],
            fn () => (bool) $this->linkedIds($save, [$a, $b]),
            fn () => PlayerLink::partnerMapFor(array_filter([$players[$a]->base_player_id, $players[$b]->base_player_id])),
        );
    }

    /**
     * Décision de la tentative, à partir de données déjà lues : vérifie score minimum, duos déjà
     * liés, délai de réessai et duo historique (sinon ValidationException), tire le dé contre la
     * chance, enregistre la tentative et son résultat. Renvoie true si le duo est confirmé.
     *
     * Les deux lectures coûteuses sont passées en fonctions pour n'être exécutées que si les
     * vérifications précédentes ont réussi (comme avant) ; l'IA, qui a déjà tout lu pour la ligue
     * (attemptForAiTeams), les fournit sans nouvelle requête.
     *
     * @param GamePlayerChemistry|null $row        ligne d'alchimie de la paire (a < b)
     * @param \Closure                 $isLinked   () => bool : l'un des deux joueurs est-il déjà dans un duo confirmé ?
     * @param \Closure                 $partnerMap () => array : duos historiques (cf. PlayerLink::partnerMapFor)
     */
    private function confirmAttempt(?GamePlayerChemistry $row, GamePlayer $playerA, GamePlayer $playerB, \Closure $isLinked, \Closure $partnerMap): bool
    {
        if (!$row || $row->score < self::MIN_SCORE || $isLinked()) {
            $this->reject('Ce duo ne peut pas encore être proposé.');
        }
        if ($row->last_attempt_at_match_count !== null
            && $row->shared_matches - $row->last_attempt_at_match_count < self::RETRY_MATCHES) {
            $this->reject('Ils doivent jouer trois nouveaux matchs ensemble avant de réessayer.');
        }

        $baseA = $playerA->base_player_id;
        $baseB = $playerB->base_player_id;
        if ($this->isHistorical($partnerMap(), $baseA, $baseB)) {
            $this->reject('Ces joueurs forment déjà un duo historique.');
        }

        $row->last_attempt_at_match_count = $row->shared_matches;
        $row->confirmed = $this->roll() <= $this->chance($row->score);
        $row->save();

        return $row->confirmed;
    }

    /** Ids (en clés) des joueurs déjà engagés dans un duo confirmé parmi ceux fournis. */
    private function linkedIds(GameSave $save, array $ids): array
    {
        return GamePlayerChemistry::where('game_save_id', $save->id)->where('confirmed', true)
            ->where(fn ($query) => $query->whereIn('player_a_id', $ids)->orWhereIn('player_b_id', $ids))
            ->get()->flatMap(fn ($row) => [$row->player_a_id, $row->player_b_id])->flip()->all();
    }

    /** Vrai si les deux joueurs de référence forment déjà un duo historique. */
    private function isHistorical(array $map, ?int $a, ?int $b): bool
    {
        return $a && $b && collect($map[$a] ?? [])->contains('partner_id', $b);
    }

    /** Probabilité de réussite en pourcentage : 40 % à 60 de score, jusqu'à 75 % à 100. */
    private function chance(int $score): int
    {
        return (int) round(40 + 35 * (max(self::MIN_SCORE, $score) - self::MIN_SCORE) / (100 - self::MIN_SCORE));
    }

    /** Jet de dé de 1 à 100 (surchargeable dans les tests). */
    protected function roll(): int
    {
        return random_int(1, 100);
    }

    /** Lève une ValidationException sur le champ « chemistry ». */
    private function reject(string $message): never
    {
        throw ValidationException::withMessages(['chemistry' => $message]);
    }
}
