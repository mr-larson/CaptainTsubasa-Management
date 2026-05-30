<?php

namespace App\Http\Controllers\GameSaves;

use App\Http\Controllers\Controller;
use App\Models\GameSaves\GameContract;
use App\Models\GameSaves\GameMatch;
use App\Models\GameSaves\GameSave;
use App\Models\GameSaves\GameTeam;
use App\Models\GameSaves\GameInjury;
use App\Models\GameSaves\GameSanction;
use App\Services\AiBonusCardService;
use App\Services\AITrainingService;
use App\Services\BonusCardActivationService;
use App\Services\BonusCardShopService;
use App\Services\MatchSimulator;
use App\Services\AITransferService;
use App\Services\AILineupService;
use App\Services\FoulAndInjuryService;
use App\Services\StaminaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class GameMatchController extends Controller
{
    /**
     * Écran de match jouable pour une session.
     */
    public function match(Request $request, GameSave $gameSave): Response
    {
        $this->authorizeSave($request, $gameSave);

        $controlledGameTeam = GameTeam::where('game_save_id', $gameSave->id)
            ->where('base_team_id', $gameSave->team_id)
            ->firstOrFail();

        $match = GameMatch::with([
            'homeTeam.contracts.gamePlayer.basePlayer',
            'awayTeam.contracts.gamePlayer.basePlayer',
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

        $homeTeam = $match->homeTeam;
        $awayTeam = $match->awayTeam;

        $isControlledHome = ((int) $controlledGameTeam->id === (int) $homeTeam->id);

        // Par défaut : mode single, côté selon domicile/extérieur de l'équipe contrôlée
        $controlMode = $request->query('controlMode', 'single');
        if (!in_array($controlMode, ['both', 'single'], true)) {
            $controlMode = 'single';
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

        return Inertia::render('Match/Engine', [
            'engineConfig' => [
                'gameSaveId'     => $gameSave->id,
                'matchId'        => $match->id,
                'week'           => $match->week,
                'maxTurns'       => 40,
                'controlMode'    => $controlMode,
                'controlledSide' => $controlledSide,

                'homeTeamName' => $homeTeam->name,
                'awayTeamName' => $awayTeam->name,
                'homeLogoUrl'  => $homeLogoUrl,
                'awayLogoUrl'  => $awayLogoUrl,
                'isControlledHome' => $isControlledHome,

                'homeTeamId' => $match->home_team_id,
                'awayTeamId' => $match->away_team_id,

                'sides' => [
                    'internalTeamId' => $internalTeam->id,
                    'externalTeamId' => $externalTeam->id,
                ],

                'teams' => [
                    'internal' => [
                        'id'        => $internalTeam->id,
                        'name'      => $internalTeam->name,
                        'logo_path' => $internalTeam->logo_path,
                        'formation' => $internalFormation,
                        'players'   => $this->mapPlayers($internalTeam, $gameSave),
                    ],
                    'external' => [
                        'id'        => $externalTeam->id,
                        'name'      => $externalTeam->name,
                        'logo_path' => $externalTeam->logo_path,
                        'formation' => $externalFormation,
                        'players'   => $this->mapPlayers($externalTeam, $gameSave),
                    ],
                ],
            ],
        ]);
    }

    /**
     * Finalise un match joué manuellement.
     */
    public function finishMatch(Request $request, GameSave $gameSave, GameMatch $match): RedirectResponse
    {
        $this->authorizeSave($request, $gameSave);

        abort_unless((int) $match->game_save_id === (int) $gameSave->id, 404);

        if ($match->status === 'played') {
            return redirect()->route('game-saves.play', $gameSave);
        }

        $data = $request->validate([
            'scoresByTeamId'   => ['required', 'array', 'min:2'],
            'scoresByTeamId.*' => ['integer', 'min:0'],
            'playerActions'    => ['array'],
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

        // 1. Sauvegarder le match avec match_stats (source de vérité)
        $match->home_score  = $homeScore;
        $match->away_score  = $awayScore;
        $match->status      = 'played';
        $match->match_stats = $data['match_stats'] ?? null;
        $match->save();

        // 2. Classement
        $home = GameTeam::where('game_save_id', $gameSave->id)->findOrFail($homeTeamId);
        $away = GameTeam::where('game_save_id', $gameSave->id)->findOrFail($awayTeamId);

        if ($homeScore > $awayScore)      { $home->wins++;   $away->losses++; }
        elseif ($homeScore < $awayScore)  { $away->wins++;   $home->losses++; }
        else                              { $home->draws++;  $away->draws++;  }

        $home->save();
        $away->save();

        // 3. Fautes, cartons et blessures EN PREMIER
        $foulEvents = $data['foulEvents'] ?? [];
        app(FoulAndInjuryService::class)->processMatchEvents($gameSave, $match, $foulEvents);

        // 4. Ajuster les lineups IA APRÈS (pour la semaine suivante)
        app(AILineupService::class)->adjustLineupsForWeek($gameSave);

        // 5. Simuler les autres matchs
        app(MatchSimulator::class)->simulateOtherMatchesOfWeek($match);
        app(AITrainingService::class)->trainForWeek($gameSave);
        app(AITransferService::class)->recruitForWeek($gameSave);

        // 6. Revenus hebdomadaires AVANT d'incrémenter la semaine
        $playedWeek = $match->week;
        $this->applyWeeklyIncome($gameSave, $playedWeek);

        // 7. Avancer la semaine
        $gameSave->week = max($gameSave->week ?? 1, $match->week + 1);
        $gameSave->save();

        // 8. Conserver player_actions
        $state = $gameSave->state ?? [];
        $state['player_actions'] = array_merge($state['player_actions'] ?? [], $data['playerActions'] ?? []);
        $gameSave->state = $state;
        $gameSave->save();

        // 9. Stamina après match
        StaminaService::applyAfterMatch($gameSave, $match);

        // 10. Consommer les cartes pre_match de l'équipe contrôlée
        $controlledTeamId = $gameSave->controlled_game_team_id;
        if ($controlledTeamId) {
            app(BonusCardActivationService::class)->consumePreMatchCards($gameSave, $controlledTeamId);
        }

        // 11. Générer la boutique + IA cartes pour la nouvelle semaine
        app(BonusCardShopService::class)->generateWeeklyOffers($gameSave);
        app(AIBonusCardService::class)->processWeek($gameSave);

        return redirect()->route('game-saves.play', $gameSave);
    }

    /**
     * Simule tous les matchs de la semaine courante.
     */
    public function simulateWeek(Request $request, GameSave $gameSave): RedirectResponse
    {
        $this->authorizeSave($request, $gameSave);

        $week = (int) ($gameSave->week ?? 1);

        $matches = GameMatch::query()
            ->where('game_save_id', $gameSave->id)
            ->where('week', $week)
            ->where('status', 'scheduled')
            ->with(['homeTeam.contracts.gamePlayer', 'awayTeam.contracts.gamePlayer'])
            ->get();

        app(MatchSimulator::class)->simulateMatchesCollection($matches);
        app(AITrainingService::class)->trainForWeek($gameSave);
        app(AITransferService::class)->recruitForWeek($gameSave);

        // Revenus hebdomadaires AVANT d'incrémenter la semaine
        $this->applyWeeklyIncome($gameSave, $week);

        $gameSave->week = $week + 1;
        $gameSave->save();

        // Générer la boutique + IA cartes pour la nouvelle semaine
        app(BonusCardShopService::class)->generateWeeklyOffers($gameSave);
        app(AIBonusCardService::class)->processWeek($gameSave);

        return redirect()->route('game-saves.play', $gameSave);
    }

    /**
     * Applique les revenus hebdomadaires à toutes les équipes.
     */
    private function applyWeeklyIncome(GameSave $gameSave, int $week): void
    {
        $BASE_INCOME = 500;
        $WIN_BONUS   = 300;
        $DRAW_BONUS  = 100;

        $teams = GameTeam::where('game_save_id', $gameSave->id)->get();

        foreach ($teams as $team) {
            $income = $BASE_INCOME;

            $match = GameMatch::where('game_save_id', $gameSave->id)
                ->where('week', $week)
                ->where('status', 'played')
                ->where(function ($q) use ($team) {
                    $q->where('home_team_id', $team->id)
                        ->orWhere('away_team_id', $team->id);
                })
                ->first();

            if ($match) {
                $isHome  = (int) $match->home_team_id === (int) $team->id;
                $scored  = $isHome ? $match->home_score : $match->away_score;
                $against = $isHome ? $match->away_score : $match->home_score;

                if ($scored > $against)       $income += $WIN_BONUS;
                elseif ($scored === $against)  $income += $DRAW_BONUS;
            }

            $team->budget = ($team->budget ?? 0) + $income;
            $team->save();
        }
    }

    // ==========================
    //   HELPERS PRIVÉS
    // ==========================

    private function mapPlayers(GameTeam $team, GameSave $gameSave): \Illuminate\Support\Collection
    {
        $state      = $gameSave->state ?? [];
        $lineups    = $state['lineup'] ?? [];
        $teamLineup = $lineups[$team->id]['slots'] ?? null;

        $currentWeek = $gameSave->week ?? 1;

        // Joueurs blessés ou suspendus cette semaine
        $injuredPlayerIds = GameInjury::where('game_save_id', $gameSave->id)
            ->where('week_return', '>', $currentWeek)
            ->pluck('game_player_id')
            ->toArray();

        $suspendedPlayerIds = GameSanction::where('game_save_id', $gameSave->id)
            ->where('week_return', '>', $currentWeek)
            ->where('weeks_suspended', '>', 0)
            ->pluck('game_player_id')
            ->toArray();

        $unavailableIds = array_unique(array_merge($injuredPlayerIds, $suspendedPlayerIds));

        $contracts = $team->contracts->loadMissing('gamePlayer');
        $starters = $contracts->sortByDesc('is_starter')->values();
        $ordered   = collect();

        if ($teamLineup) {
            for ($slot = 1; $slot <= 11; $slot++) {
                $pid = $teamLineup[$slot] ?? null;
                if ($pid) {
                    $c = $starters->firstWhere('game_player_id', $pid);
                    if ($c) {
                        $ordered->push([$slot, $c]);
                        $starters = $starters->reject(fn($cc) => $cc->id === $c->id)->values();
                        continue;
                    }
                }
                $ordered->push([$slot, null]);
            }
        } else {
            for ($slot = 1; $slot <= 11; $slot++) {
                $ordered->push([$slot, $starters[$slot - 1] ?? null]);
            }
        }

        $starters11 = $ordered->map(function (array $row) use ($unavailableIds) {
            [$slot, $c] = $row;
            if (!$c || !$c->gamePlayer) {
                return [
                    'id' => null, 'number' => $slot,
                    'firstname' => '', 'lastname' => '', 'position' => '',
                    'is_starter' => false, 'photo_path' => null, 'photo_url' => null,
                    'stats' => null, 'special_moves' => [], 'is_available' => true,
                    'yellow_cards' => 0,
                    'is_captain'                => $c->is_captain ?? false,
                    'contract_id'               => $c->id,
                    'captain_rerolls_remaining' => $c->captain_rerolls_remaining ?? 3,
                    'captain_reroll_used_this_action' => false,
                ];
            }
            $p = $c->gamePlayer;
            return [
                'id'            => $p->id,
                'number'        => $slot,
                'firstname'     => $p->firstname,
                'lastname'      => $p->lastname,
                'position'      => $p->position,
                'is_starter'    => true,
                'photo_path'    => $p->photo_path,
                'photo_url'     => $p->photo_path ? Storage::url($p->photo_path) : null,
                'stats'         => [
                    'speed' => $p->speed, 'stamina' => $p->stamina,
                    'attack' => $p->attack, 'defense' => $p->defense,
                    'shot' => $p->shot, 'pass' => $p->pass, 'dribble' => $p->dribble,
                    'block' => $p->block, 'intercept' => $p->intercept, 'tackle' => $p->tackle,
                    'hand_save' => $p->hand_save, 'punch_save' => $p->punch_save,
                ],
                'special_moves' => $p->special_moves ?? [],
                'is_available'  => !in_array($p->id, $unavailableIds),
                'yellow_cards'  => 0,
                // ── Captain ──
                'is_captain'                      => $c->is_captain,
                'captain_rerolls_remaining'       => $c->captain_rerolls_remaining,
                'captain_reroll_used_this_action' => $c->captain_reroll_used_this_action,
            ];
        })->values();

        $subContracts = $team->contracts
            ->filter(fn($c) => !$c->is_starter && $c->gamePlayer)
            ->values();
        $subs = $subContracts->map(function ($c) use ($unavailableIds) {
            $p = $c->gamePlayer;
            return [
                'id'            => $p->id,
                'number'        => $p->number ?? $p->id,
                'firstname'     => $p->firstname,
                'lastname'      => $p->lastname,
                'position'      => $p->position,
                'is_starter'    => false,
                'photo_path'    => $p->photo_path,
                'photo_url'     => $p->photo_path ? Storage::url($p->photo_path) : null,
                'stats'         => [
                    'speed' => $p->speed, 'stamina' => $p->stamina,
                    'attack' => $p->attack, 'defense' => $p->defense,
                    'shot' => $p->shot, 'pass' => $p->pass, 'dribble' => $p->dribble,
                    'block' => $p->block, 'intercept' => $p->intercept, 'tackle' => $p->tackle,
                    'hand_save' => $p->hand_save, 'punch_save' => $p->punch_save,
                ],
                'special_moves' => $p->special_moves ?? [],
                'is_available'  => !in_array($p->id, $unavailableIds),
                'yellow_cards'  => 0,
                // ── Captain ──
                'is_captain'                      => $c->is_captain,
                'captain_rerolls_remaining'       => $c->captain_rerolls_remaining,
                'captain_reroll_used_this_action' => $c->captain_reroll_used_this_action,
            ];
        })->values();

        return $starters11->concat($subs);
    }

    public function useCaptainReroll(Request $request, GameSave $gameSave, GameContract $contract): JsonResponse
    {
        $this->authorizeSave($request, $gameSave);

        if ($contract->game_save_id !== $gameSave->id) abort(403);

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
        $this->authorizeSave($request, $gameSave);

        if ($contract->game_save_id !== $gameSave->id) abort(403);

        $contract->resetRerollActionFlag();

        return response()->json(['success' => true]);
    }

    private function authorizeSave(Request $request, GameSave $gameSave): void
    {
        if ($gameSave->user_id !== $request->user()->id) {
            abort(403);
        }
    }
}
