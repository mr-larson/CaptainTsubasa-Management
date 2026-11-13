<?php

namespace App\Http\Controllers\GameSaves;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\AuthorizesGameSave;
use App\Http\Controllers\Concerns\LocksGameSave;
use App\Models\GameSaves\GameContract;
use App\Models\GameSaves\GamePlayer;
use App\Models\GameSaves\GameSave;
use App\Models\GameSaves\GameTeam;
use App\Services\SeasonChallengeService;
use App\Services\MoraleService;
use App\Services\SeasonService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Recrutement d'un agent libre par l'équipe contrôlée.
 */
class FreeAgentController extends Controller
{
    use AuthorizesGameSave;
    use LocksGameSave;

    public function signFreeAgent(Request $request, GameSave $gameSave, GamePlayer $player): RedirectResponse
    {
        $this->authorizeGameSave('update', $gameSave, $player);

        // Coupe du Monde : des sélections nationales ne recrutent pas.
        if ($gameSave->competition_type === 'world_cup') {
            return back()->withErrors(['player' => 'Pas de transferts en Coupe du Monde.']);
        }

        $data = $request->validate([
            'team_id'       => ['required', 'integer', Rule::exists('game_teams', 'id')->where('game_save_id', $gameSave->id)],
            'matches_total' => ['required', 'integer', 'min:1'],
            'reason'        => ['nullable', 'string', 'max:1000'],
        ]);

        // Verrou par save : pas de double contrat ni de double débit sur un
        // double clic (contrat existant et budget relus sous verrou).
        return $this->withSaveLock($gameSave, function (GameSave $gameSave) use ($data, $player) {
            $player->refresh();

            $team = GameTeam::where('game_save_id', $gameSave->id)->findOrFail($data['team_id']);

            $startWeek = $gameSave->week ?? 1;
            $remainingWeeks = max(0, app(SeasonService::class)->getSeasonLength($gameSave) - $startWeek);
            if (($gameSave->phase ?? 'season') !== 'season' || $remainingWeeks === 0) {
                return back()->withErrors(['matches_total' => 'Il ne reste plus de semaines disponibles pour signer un joueur.']);
            }
            if ($data['matches_total'] > $remainingWeeks) {
                return back()->withErrors(['matches_total' => "La durée maximale restante est de {$remainingWeeks} semaine(s)."]);
            }

            $alreadyHasContract = GameContract::where('game_save_id', $gameSave->id)
                ->where('game_player_id', $player->id)
                ->activeAt($startWeek)
                ->exists();

            if ($alreadyHasContract) {
                return back()->with('info', 'Ce joueur a déjà un contrat en cours dans cette partie.');
            }

            // Le salaire fait autorité côté serveur : on ignore toute valeur cliente
            // (sinon n'importe qui pourrait signer un joueur pour 0) et on refuse la
            // signature si l'équipe n'a pas les moyens.
            $salary    = (int) $player->adjusted_cost;
            $totalCost = $salary * $data['matches_total'];
            $budget    = (int) ($team->budget ?? 0);

            if ($totalCost > $budget) {
                $totalFmt  = number_format($totalCost, 0, ',', ' ');
                $salaryFmt = number_format($salary, 0, ',', ' ');
                $budgetFmt = number_format($budget, 0, ',', ' ');
                return back()->withErrors([
                    'contract' => "Budget insuffisant : {$player->full_name} coûte {$totalFmt} "
                        . "({$salaryFmt} × {$data['matches_total']} match(s)), budget disponible {$budgetFmt}.",
                ]);
            }

            // Conséquences du moral : un révolté refuse de re-signer avec son ancien club ;
            // signer ailleurs repart sur un moral neutre.
            $lastContract = GameContract::where('game_save_id', $gameSave->id)
                ->where('game_player_id', $player->id)
                ->orderByDesc('end_week')
                ->first();
            $sameTeam = $lastContract && (int) $lastContract->game_team_id === (int) $team->id;

            if ($sameTeam && MoraleService::refusesToSign($player)) {
                return back()->withErrors([
                    'contract' => "{$player->full_name} refuse de re-signer avec ce club (moral ou relation avec le coach au plus bas).",
                ]);
            }

            if ($lastContract && !$sameTeam) {
                $player->morale         = MoraleService::NEUTRAL_MORALE;
                $player->coach_affinity = 0;
            }

            $endWeek      = $startWeek + $data['matches_total'] - 1;
            $starterCount = GameContract::where('game_save_id', $gameSave->id)
                ->where('game_team_id', $team->id)
                ->where('is_starter', true)
                ->activeAt($startWeek)
                ->count();

            // Premier numéro disponible dans l'équipe
            $usedNumbers = GamePlayer::where('game_save_id', $gameSave->id)
                ->whereHas('contracts', fn($q) => $q->where('game_team_id', $team->id)->activeAt($startWeek))
                ->pluck('number')
                ->filter()
                ->toArray();

            $nextNumber = 1;
            while (in_array($nextNumber, $usedNumbers)) {
                $nextNumber++;
            }

            $player->number = $nextNumber;
            $player->save();

            GameContract::create([
                'game_save_id'                    => $gameSave->id,
                'game_team_id'                    => $team->id,
                'game_player_id'                  => $player->id,
                'salary'                          => $salary,
                'start_week'                      => $startWeek,
                'end_week'                        => $endWeek,
                'is_starter'                      => $starterCount < 11,
                'is_captain'                      => false,
                'captain_rerolls_remaining'       => 3,
                'captain_reroll_used_this_action' => false,
            ]);

            $team->budget = $budget - $totalCost;
            $team->save();

            $gameSave->gameState()->recordFreeAgentSigning([
                'player_id'     => $player->id,
                'team_id'       => $team->id,
                'salary'        => $salary,
                'matches_total' => $data['matches_total'],
                'total_cost'    => $totalCost,
                'reason'        => $data['reason'] ?? null,
                'week'          => $gameSave->week,
            ]);
            $gameSave->save();

            app(SeasonChallengeService::class)->payCompleted($gameSave, [(int) $team->id], ['signings_min']);

            return back()->with('success', 'Joueur signé et budget mis à jour.');
        });
    }
}
