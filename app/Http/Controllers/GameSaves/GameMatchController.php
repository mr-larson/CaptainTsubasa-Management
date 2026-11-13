<?php

namespace App\Http\Controllers\GameSaves;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\AuthorizesGameSave;
use App\Http\Controllers\Concerns\LocksGameSave;
use App\Models\GameSaves\GameContract;
use App\Models\GameSaves\GameMatch;
use App\Models\GameSaves\GameSave;
use App\Models\GameSaves\GameTeam;
use App\Models\GameSaves\GameInjury;
use App\Models\GameSaves\GameSanction;
use App\Services\AiLineUpService;
use App\Services\CareerObjectiveService;
use App\Services\FoulAndInjuryService;
use App\Services\MatchRosterPresenter;
use App\Services\MatchSimulator;
use App\Services\DeclarationService;
use App\Services\PostMatchProgressionService;
use App\Services\PlayerChemistryService;
use App\Services\SeasonChallengeService;
use App\Services\SeasonService;
use App\Services\WeekClosingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GameMatchController extends Controller
{
    use AuthorizesGameSave;
    use LocksGameSave;

    /**
     * Écran de match jouable pour une session.
     */
    public function match(Request $request, GameSave $gameSave): Response|RedirectResponse
    {
        $this->authorizeGameSave('view', $gameSave);
        if ($gameSave->hasPendingMidseasonBreak()) {
            return redirect()->route('game-saves.Play', $gameSave);
        }

        // Équipe jouée = joueur ACTIF (hot-seat), repli sur l'équipe propriétaire.
        $controlledGameTeam = ($gameSave->controlled_game_team_id
            ? GameTeam::where('game_save_id', $gameSave->id)->find($gameSave->controlled_game_team_id)
            : null)
            ?? GameTeam::where('game_save_id', $gameSave->id)
                ->where('base_team_id', $gameSave->team_id)
                ->firstOrFail();

        // Seuls les contrats actifs cette semaine : ni contrat expiré, ni
        // pré-contrat futur sur la feuille de match (comme simulateWeek et play).
        $week = (int) ($gameSave->week ?? 1);
        $activeContracts = fn ($q) => $q->activeAt($week)->with('gamePlayer.basePlayer');

        $match = GameMatch::with([
            'homeTeam.contracts' => $activeContracts,
            'awayTeam.contracts' => $activeContracts,
        ])
            ->where('game_save_id', $gameSave->id)
            ->where('status', 'scheduled')
            ->where('week', '>=', $gameSave->week ?? 1)
            ->where(function ($q) use ($controlledGameTeam) {
                $q->where('home_team_id', $controlledGameTeam->id)
                    ->orWhere('away_team_id', $controlledGameTeam->id);
            })
            ->orderBy('week')
            ->firstOrFail();

        // 3 relances de capitaine par match : remises à zéro au lancement (elles
        // sont décomptées côté serveur pendant le match via useCaptainReroll).
        GameContract::where('game_save_id', $gameSave->id)
            ->whereIn('game_team_id', [$match->home_team_id, $match->away_team_id])
            ->where('is_captain', true)
            ->update(['captain_rerolls_remaining' => 3, 'captain_reroll_used_this_action' => false]);
        if ((int) $match->week === $week) {
            $this->withSaveLock($gameSave, function (GameSave $save) use ($match, $week) {
                if ((int) $save->week !== $week || $save->hasPendingMidseasonBreak()) return;
                if ($match->fresh()?->status !== \App\Enums\MatchStatus::Scheduled) return;

                app(AiLineUpService::class)->adjustLineupsForWeek($save, [$match->home_team_id, $match->away_team_id]);
            });
        }
        $match->load(['homeTeam.contracts' => $activeContracts, 'awayTeam.contracts' => $activeContracts]);

        $homeTeam = $match->homeTeam;
        $awayTeam = $match->awayTeam;

        $isControlledHome = ((int) $controlledGameTeam->id === (int) $homeTeam->id);

        // Par défaut : mode single, côté selon domicile/extérieur de l'équipe contrôlée
        $controlMode = $request->query('controlMode', 'single');
        if (!in_array($controlMode, ['both', 'single'], true)) {
            $controlMode = 'single';
        }

        // Hot-seat : derby entre deux managers humains → les deux camps se
        // jouent à la main (sinon l'IA jouerait l'équipe du second humain).
        $humanIds = array_map('intval', $gameSave->controlledGameTeamIds());
        if (in_array((int) $homeTeam->id, $humanIds, true) && in_array((int) $awayTeam->id, $humanIds, true)) {
            $controlMode = 'both';
        }

        $controlledSide = 'internal';

        $internalTeam = $isControlledHome ? $homeTeam : $awayTeam;
        $externalTeam = $isControlledHome ? $awayTeam : $homeTeam;

        $state   = $gameSave->state ?? [];
        $lineups = $state['lineup'] ?? [];

        $internalFormation = $internalTeam->formation ?? '3-2-3-2';
        $externalFormation = $externalTeam->formation ?? '3-2-3-2';

        $homeLogoUrl = $homeTeam->logo_path ? '/' . ltrim($homeTeam->logo_path, '/') : null;
        $awayLogoUrl = $awayTeam->logo_path ? '/' . ltrim($awayTeam->logo_path, '/') : null;

        $ranks = app(SeasonService::class)->sortStandings(
            GameTeam::where('game_save_id', $gameSave->id)->get()
        )->pluck('id')->flip()->map(fn ($index) => $index + 1);

        return Inertia::render('Match/Engine', [
            'engineConfig' => [
                'gameSaveId'     => $gameSave->id,
                'matchId'        => $match->id,
                'week'           => $match->week,
                'maxTurns'       => (int) $gameSave->getConfig('match_max_turns', 30),
                'criticalsEnabled' => (bool) $gameSave->getConfig('match_criticals_enabled'),
                'maxSubstitutions' => (int) $gameSave->getConfig('match_max_substitutions'),
                // Coupe du Monde, phase finale : égalité → tirs au but dans le moteur.
                'isKnockout'     => in_array($match->round, ['semi', 'final'], true),
                'controlMode'    => $controlMode,
                'controlledSide' => $controlledSide,

                'homeTeamName' => $homeTeam->name,
                'awayTeamName' => $awayTeam->name,
                'homeLogoUrl'  => $homeLogoUrl,
                'awayLogoUrl'  => $awayLogoUrl,
                'isControlledHome' => $isControlledHome,

                'homeTeamId' => $match->home_team_id,
                'internalIsHome' => $internalTeam->id === $match->home_team_id,
                'awayTeamId' => $match->away_team_id,

                'sides' => [
                    'internalTeamId' => $internalTeam->id,
                    'externalTeamId' => $externalTeam->id,
                ],

                'teams' => [
                    'internal' => [
                        'id'        => $internalTeam->id,
                        'name'      => $internalTeam->name,
                        'description' => $internalTeam->description,
                        'rank' => $ranks->get($internalTeam->id),
                        'logo_path' => $internalTeam->logo_path,
                        'formation' => $internalFormation,
                        'tacticalStyle' => $internalTeam->tactical_style,
                        'managementPhilosophy' => $internalTeam->management_philosophy,
                        'players'   => app(MatchRosterPresenter::class)->forTeam($internalTeam, $gameSave, $match->id),
                    ],
                    'external' => [
                        'id'        => $externalTeam->id,
                        'name'      => $externalTeam->name,
                        'description' => $externalTeam->description,
                        'rank' => $ranks->get($externalTeam->id),
                        'logo_path' => $externalTeam->logo_path,
                        'formation' => $externalFormation,
                        'tacticalStyle' => $externalTeam->tactical_style,
                        'managementPhilosophy' => $externalTeam->management_philosophy,
                        'players'   => app(MatchRosterPresenter::class)->forTeam($externalTeam, $gameSave, $match->id),
                    ],
                ],
            ],
        ]);
    }

    /**
     * Détail d'un match joué (stats, déroulé, effectifs), chargé à la demande
     * par le calendrier : la page Play ne reçoit pas les match_stats. Seul
     * endroit où le déroulé (game_match_events) est lu.
     */
    public function stats(GameSave $gameSave, GameMatch $match): JsonResponse
    {
        $this->authorizeGameSave('view', $gameSave, $match);

        // Cartons et blessures de ce match (source fiable pour la feuille de match,
        // y compris pour les matchs simulés qui n'ont pas ces événements dans le déroulé).
        $incidents = [
            'sanctions' => GameSanction::where('game_match_id', $match->id)
                ->get(['game_player_id', 'type', 'weeks_suspended', 'week_return'])
                ->map(fn ($s) => [
                    'player_id'       => (int) $s->game_player_id,
                    'type'            => $s->type,
                    'weeks_suspended' => (int) $s->weeks_suspended,
                    'week_return'     => $s->week_return,
                ])->values(),
            'injuries' => GameInjury::where('game_match_id', $match->id)
                ->get(['game_player_id', 'description', 'weeks_out', 'week_return'])
                ->map(fn ($i) => [
                    'player_id'   => (int) $i->game_player_id,
                    'description' => $i->description,
                    'weeks_out'   => (int) $i->weeks_out,
                    'week_return' => $i->week_return,
                ])->values(),
        ];

        return response()->json([
            'match_stats' => $match->fullStats(),
            'incidents' => $incidents,
        ]);
    }

    /**
     * Classements d'une journée (onglet Calendrier) : hommes du match,
     * buteurs, passeurs, dribbleurs, gardiens, défenseurs, duels.
     */
    public function weekLeaders(GameSave $gameSave, int $week): JsonResponse
    {
        $this->authorizeGameSave('view', $gameSave);

        return response()->json(app(\App\Services\WeekLeadersService::class)->forWeek($gameSave, $week));
    }

    /**
     * Finalise un match joué manuellement.
     */
    public function finishMatch(Request $request, GameSave $gameSave, GameMatch $match): RedirectResponse
    {
        $this->authorizeGameSave('update', $gameSave);

        abort_unless((int) $match->game_save_id === (int) $gameSave->id, 404);

        $data = $request->validate([
            'scoresByTeamId'   => ['required', 'array', 'min:2'],
            'scoresByTeamId.*' => ['integer', 'min:0'],
            'match_stats'      => ['nullable', 'array'],
            'foulEvents'       => ['nullable', 'array'],
        ]);

        $scores     = $data['scoresByTeamId'];
        $homeTeamId = (int) $match->home_team_id;
        $awayTeamId = (int) $match->away_team_id;

        if (!array_key_exists($homeTeamId, $scores) || !array_key_exists($awayTeamId, $scores)) {
            abort(422, 'Scores incomplets pour ce match.');
        }

        $homeScore = (int) $scores[$homeTeamId];
        $awayScore = (int) $scores[$awayTeamId];

        // Verrou par save : un double clic ou un retry attend la première
        // requête puis retombe sur le match déjà joué, au lieu de recompter
        // classement, revenus et fin de semaine. Tout est annulé en cas d'erreur.
        return $this->withSaveLock($gameSave, function (GameSave $gameSave) use ($match, $data, $homeTeamId, $awayTeamId, $homeScore, $awayScore) {
            $match->refresh();

            if ($match->status === \App\Enums\MatchStatus::Played || ! $gameSave->isInMatchPhase() || $gameSave->hasPendingMidseasonBreak()) {
                return redirect()->route('game-saves.Play', $gameSave);
            }

            // Un match d'une semaine future ferait sauter les semaines intermédiaires.
            if ((int) $match->week > (int) ($gameSave->week ?? 1)) {
                abort(422, 'Ce match n\'est pas encore programmé.');
            }

            // ─────────────────────────────────────────────────────────────
            //  PARTIE PAR-MATCH : toujours exécutée pour le match qui vient
            //  d'être joué (score, classement, fautes, progression, cartes).
            // ─────────────────────────────────────────────────────────────

            // 1. Sauvegarder le match avec match_stats (source de vérité).
            // Tirs au but : gardés seulement pour une égalité en phase finale,
            // avec un vainqueur qui dispute bien ce match.
            $stats    = $data['match_stats'] ?? null;
            $shootout = $stats['shootout'] ?? null;
            if (is_array($stats) && array_key_exists('shootout', $stats)) {
                $validShootout = in_array($match->round, ['semi', 'final'], true)
                    && $homeScore === $awayScore
                    && in_array((int) ($shootout['winner_team_id'] ?? 0), [$homeTeamId, $awayTeamId], true);
                if (! $validShootout) {
                    unset($stats['shootout']);
                }
            }

            // Instantané des effectifs : l'historique reste juste après les transferts.
            if (is_array($stats)) {
                unset($stats['chemistry_recorded']);
                $stats['lineups'] = app(\App\Services\MatchLineupSnapshot::class)->build($match);
            }

            $match->home_score  = $homeScore;
            $match->away_score  = $awayScore;
            $match->status      = 'played';
            $match->match_stats = $stats;
            $match->save();

            // 2. Classement
            $home = GameTeam::where('game_save_id', $gameSave->id)->findOrFail($homeTeamId);
            $away = GameTeam::where('game_save_id', $gameSave->id)->findOrFail($awayTeamId);

            if ($homeScore > $awayScore)      { $home->wins++;   $away->losses++; }
            elseif ($homeScore < $awayScore)  { $away->wins++;   $home->losses++; }
            else                              { $home->draws++;  $away->draws++;  }

            $home->goals_for     += $homeScore;
            $home->goals_against += $awayScore;
            $away->goals_for     += $awayScore;
            $away->goals_against += $homeScore;

            $home->save();
            $away->save();

            // 3. Fautes, cartons et blessures (propres à ce match)
            $foulEvents = $data['foulEvents'] ?? [];
            app(FoulAndInjuryService::class)->processMatchEvents($gameSave, $match, $foulEvents);

            // 4. Progression post-match (joueurs de ce match)
            app(PostMatchProgressionService::class)->applyForMatch($gameSave, $match);
            app(PlayerChemistryService::class)->recordMatch($gameSave, $match);
            $gameSave->refresh();
            app(DeclarationService::class)->consumeMatchBoosts($gameSave, $match->id);
            app(SeasonChallengeService::class)->payCompleted($gameSave, [(int) $homeTeamId, (int) $awayTeamId]);


            return $this->continueAfterMatch($gameSave, (int) $match->week);
        });
    }

    public function simulateMatch(GameSave $gameSave, GameMatch $match, MatchSimulator $simulator): RedirectResponse
    {
        $this->authorizeGameSave('update', $gameSave, $match);

        return $this->withSaveLock($gameSave, function (GameSave $save) use ($match, $simulator) {
            $match->refresh();
            if ($match->status !== \App\Enums\MatchStatus::Scheduled || (int) $match->week !== (int) $save->week
                || !$save->isInMatchPhase() || $save->hasPendingMidseasonBreak()) {
                return redirect()->route('game-saves.Play', $save);
            }

            if (app(CareerObjectiveService::class)->isGameOver($save)) {
                return redirect()->route('game-saves.game-over', $save);
            }

            $activeTeamId = (int) $save->controlled_game_team_id;
            if (!$activeTeamId && $save->team_id) {
                $activeTeamId = (int) GameTeam::where('game_save_id', $save->id)
                    ->where('base_team_id', $save->team_id)->value('id');
            }
            $homeId = (int) $match->home_team_id;
            $awayId = (int) $match->away_team_id;
            abort_unless($activeTeamId && in_array($activeTeamId, [$homeId, $awayId], true), 403);
            $opponentId = $homeId === $activeTeamId ? $awayId : $homeId;
            abort_if(in_array($opponentId, $save->controlledGameTeamIds(), true), 403, 'Ce match oppose deux managers humains.');

            app(AiLineUpService::class)->adjustLineupsForWeek($save, [$opponentId]);
            $simulator->simulateMatchesCollection(collect([$match]), $save);
            $save->refresh();
            app(SeasonChallengeService::class)->payCompleted($save, [$homeId, $awayId]);

            $result = "{$match->homeTeam->name} {$match->home_score}–{$match->away_score} {$match->awayTeam->name}";

            return $this->continueAfterMatch($save, (int) $match->week)->with('success', "Match simulé · {$result}");
        });
    }

    private function continueAfterMatch(GameSave $save, int $week): RedirectResponse
    {
        // Les autres managers jouent leur match avant la clôture de la semaine.
        $next = $this->nextHumanWithPendingMatch($save, $week);
        if ($next) {
            $save->controlled_game_team_id = $next->id;
            $save->save();

            return redirect()->route('game-saves.Play', $save);
        }

        return $this->redirectAfterWeek($save, app(WeekClosingService::class)->close($save, $week));
    }

    /**
     * Prochain joueur humain (par ordre de siège) dont le match de la semaine
     * n'est pas encore joué. Null si plus aucun.
     */
    private function nextHumanWithPendingMatch(GameSave $gameSave, int $week): ?GameTeam
    {
        foreach ($gameSave->controlledGameTeams()->get() as $team) {
            $hasPending = GameMatch::where('game_save_id', $gameSave->id)
                ->where('week', $week)
                ->where('status', 'scheduled')
                ->where(function ($q) use ($team) {
                    $q->where('home_team_id', $team->id)
                        ->orWhere('away_team_id', $team->id);
                })
                ->exists();

            if ($hasPending) {
                return $team;
            }
        }

        return null;
    }

    /**
     * Simule tous les matchs de la semaine courante.
     */
    public function simulateWeek(Request $request, GameSave $gameSave): RedirectResponse
    {
        $this->authorizeGameSave('update', $gameSave);

        // Verrou par save + garde de phase : un double clic ne simule pas deux
        // semaines, et un re-POST pendant la draft / la fin de saison ne rejoue
        // pas endSeason (primes et titres comptés deux fois).
        return $this->withSaveLock($gameSave, function (GameSave $gameSave) use ($request) {
            if (! $gameSave->isInMatchPhase()) {
                return redirect()->route('game-saves.Play', $gameSave);
            }

            if (app(CareerObjectiveService::class)->isGameOver($gameSave)) {
                return redirect()->route('game-saves.game-over', $gameSave);
            }

            $week = (int) ($gameSave->week ?? 1);

            // Idempotence : le front envoie la semaine affichée. Un second POST
            // (double clic) arrive après l'avancement et ne simule pas la
            // semaine suivante, ni le match que le joueur devait disputer.
            if ($request->filled('week') && (int) $request->input('week') !== $week) {
                return redirect()->route('game-saves.Play', $gameSave);
            }

            if ($request->boolean('midseason_break')) {
                app(SeasonService::class)->completeMidseasonBreak($gameSave);
                return redirect()->route('game-saves.Play', $gameSave);
            }
            if ($gameSave->hasPendingMidseasonBreak()) {
                return redirect()->route('game-saves.Play', $gameSave);
            }

            // Hot-seat : on ne simule jamais le match d'un manager humain. Si
            // l'un d'eux doit encore jouer, la main lui passe.
            $nextHuman = $this->nextHumanWithPendingMatch($gameSave, $week);
            if ($nextHuman) {
                $gameSave->controlled_game_team_id = $nextHuman->id;
                $gameSave->save();

                return redirect()->route('game-saves.Play', $gameSave);
            }

            return $this->redirectAfterWeek(
                $gameSave,
                app(WeekClosingService::class)->close($gameSave, $week),
            );
        });
    }

    /**
     * Écran à afficher après la clôture d'une semaine (WeekClosingService).
     */
    private function redirectAfterWeek(GameSave $gameSave, string $outcome): RedirectResponse
    {
        return match ($outcome) {
            WeekClosingService::OUTCOME_GAME_OVER  => redirect()->route('game-saves.game-over', $gameSave),
            WeekClosingService::OUTCOME_SEASON_END => redirect()->route('game-saves.season-end', $gameSave),
            default                                => redirect()->route('game-saves.Play', $gameSave),
        };
    }

    // ==========================
    //   HELPERS PRIVÉS
    // ==========================

    public function useCaptainReroll(Request $request, GameSave $gameSave, GameContract $contract): JsonResponse
    {
        $this->authorizeGameSave('update', $gameSave, $contract);

        if (! $contract->useReroll()) {
            return response()->json([
                'success' => false,
                'message' => 'Reroll indisponible.',
            ], 400);
        }

        return response()->json([
            'success'           => true,
            'rerollsRemaining'  => $contract->captain_rerolls_remaining,
        ]);
    }

    public function resetCaptainRerollActionFlag(Request $request, GameSave $gameSave, GameContract $contract): JsonResponse
    {
        $this->authorizeGameSave('update', $gameSave, $contract);

        $contract->resetRerollActionFlag();

        return response()->json(['success' => true]);
    }
}
