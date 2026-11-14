<?php
namespace App\Http\Controllers\GameSaves;

use App\Http\Requests\GameSaves\SubstitutePlayerRequest;
use App\Http\Requests\GameSaves\UpdateFormationRequest;
use App\Http\Requests\GameSaves\UpdateLineupRequest;
use App\Http\Requests\GameSaves\ToggleStarterRequest;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\AuthorizesGameSave;
use App\Http\Controllers\Concerns\LocksGameSave;
use App\Models\GameSaves\GameContract;
use App\Models\GameSaves\GameSave;
use Illuminate\Http\Request;

/** Composition d'équipe de la sauvegarde : titulaires et remplaçants, capitaine, formation, placement sur le terrain et remplacements. Les actions exigent le droit de modifier la sauvegarde et le verrou de sauvegarde, et ne portent que sur l'équipe gérée par le joueur (ou une équipe IA si l'option « Accès aux autres clubs » est active). */
class LineupController extends Controller
{
    use AuthorizesGameSave;
    use LocksGameSave;

    /**
     * Toggle titulaire / remplaçant sur un contrat.
     */
    public function toggleStarter(ToggleStarterRequest $request, GameSave $gameSave, GameContract $contract)
    {
        $this->authorizeGameSave('update', $gameSave, $contract);
        $data = $request->validated();

        return $this->withSaveLock($gameSave, function (GameSave $gameSave) use ($contract, $data) {
            $contract->refresh();
            if ($refused = $this->refuseUnlessActiveTeam($gameSave, (int) $contract->game_team_id)) return $refused;
            $week = (int) $gameSave->week;
            if (!$contract->isActive($week)) {
                return back()->withErrors(['is_starter' => 'Ce contrat n’est plus actif.']);
            }

            $starterIds = GameContract::where('game_team_id', $contract->game_team_id)
                ->activeAt($week)
                ->where('is_starter', true)
                ->distinct()
                ->pluck('game_player_id');
            if (!$contract->is_starter && $starterIds->count() >= 11) {
                return back()->withErrors(['is_starter' => 'Le onze est complet : choisis le titulaire à remplacer.']);
            }

            $contract->is_starter = !$contract->is_starter;
            $contract->save();

            $this->syncLineupSlot($gameSave, $contract, $starterIds->all(), $data['slot'] ?? null);

            return back()->with('success', $contract->is_starter ? 'Joueur titularisé.' : 'Joueur retiré du onze.');
        });
    }

    /**
     * Garde state.lineup cohérent avec is_starter : un joueur passé remplaçant
     * libère son slot (sinon il s'affiche à la fois sur le terrain et sur le
     * banc) ; un joueur titularisé prend le slot demandé s'il est libre, sinon
     * le premier slot libre.
     */
    private function syncLineupSlot(GameSave $gameSave, GameContract $contract, array $starterIds, ?int $preferredSlot = null): void
    {
        $teamId = $contract->game_team_id;
        $slots  = $gameSave->gameState()->lineupSlots($teamId);
        if (!is_array($slots)) {
            return; // pas de compo enregistrée : l'ordre par défaut s'applique
        }

        $playerId = (int) $contract->game_player_id;
        $placed   = false;
        foreach ($slots as $slot => $pid) {
            if ((int) $pid !== $playerId && !in_array((int) $pid, $starterIds, true)) {
                $slots[$slot] = null;
            }
            if ((int) $pid === $playerId) {
                if ($contract->is_starter) {
                    $placed = true;
                } else {
                    $slots[$slot] = null;
                }
            }
        }

        if ($contract->is_starter && !$placed && $preferredSlot && empty($slots[$preferredSlot] ?? null)) {
            $slots[$preferredSlot] = $playerId;
            $placed = true;
        }

        if ($contract->is_starter && !$placed) {
            for ($slot = 1; $slot <= 11; $slot++) {
                if (empty($slots[$slot] ?? null)) {
                    $slots[$slot] = $playerId;
                    break;
                }
            }
        }

        $gameSave->gameState()->setLineupSlots($teamId, $slots);
        $gameSave->save();
    }

    /**
     * Toggle capitaine sur un contrat.
     * Un seul capitaine par équipe : désigne le nouveau et retire l'ancien.
     */
    public function toggleCaptain(Request $request, GameSave $gameSave, GameContract $contract)
    {
        $this->authorizeGameSave('update', $gameSave, $contract);

        // Sous verrou : retirer l'ancien capitaine puis poser le nouveau est
        // atomique, et deux requêtes simultanées ne laissent pas deux capitaines.
        return $this->withSaveLock($gameSave, function (GameSave $gameSave) use ($contract) {
            $contract->refresh();
            if ($refused = $this->refuseUnlessActiveTeam($gameSave, (int) $contract->game_team_id)) return $refused;

            // Si ce joueur est déjà capitaine → on le retire simplement
            if ($contract->is_captain) {
                $contract->is_captain = false;
                $contract->save();
                return back()->with('success', 'Capitaine retiré.');
            }

            // Sinon : retirer le capitaine actuel de la même équipe
            GameContract::where('game_team_id', $contract->game_team_id)
                ->where('is_captain', true)
                ->where('id', '!=', $contract->id)
                ->update(['is_captain' => false]);

            // Désigner le nouveau
            $contract->is_captain = true;
            $contract->save();

            return back()->with('success', 'Nouveau capitaine désigné.');
        });
    }

    /**
     * Sauvegarde la composition (slots) ET la formation d'une équipe.
     */
    public function update(UpdateLineupRequest $request, GameSave $gameSave)
    {
        $this->authorizeGameSave('update', $gameSave);

        $data = $request->validated();

        $teamId = (int) $data['team_id'];

        // Sous verrou : state.lineup est relu et réécrit en entier, une sauvegarde
        // concurrente (autre onglet, autre siège) ne doit pas l'écraser.
        return $this->withSaveLock($gameSave, function (GameSave $gameSave) use ($data, $teamId) {
            if ($refused = $this->refuseUnlessActiveTeam($gameSave, $teamId)) return $refused;
            $team   = $gameSave->gameTeams()->where('id', $teamId)->firstOrFail();

            $slots = [];
            foreach ($data['slots'] as $row) {
                $slots[(int) $row['slot']] = $row['player_id'] ? (int) $row['player_id'] : null;
            }

            // Les joueurs placés doivent être sous contrat actif avec cette équipe :
            // sinon la composition pourrait aligner un joueur d'un autre club.
            $playerIds = array_values(array_filter($slots));
            $underContract = GameContract::where('game_save_id', $gameSave->id)
                ->where('game_team_id', $team->id)
                ->activeAt((int) ($gameSave->week ?? 1))
                ->whereIn('game_player_id', $playerIds)
                ->distinct()
                ->count('game_player_id');
            if ($underContract !== count($playerIds)) {
                return back()->withErrors(['slots' => "La composition contient un joueur qui ne fait pas partie de l'équipe."]);
            }

            $gameSave->gameState()->setLineupSlots($team->id, $slots);
            $gameSave->save();

            if (!empty($data['formation'])) {
                $team->formation = $data['formation'];
                $team->save();
            }

            return back()->with('success', 'Composition mise à jour.');
        });
    }

    /**
     * Sauvegarde uniquement la formation d'une équipe.
     */
    public function updateFormation(UpdateFormationRequest $request, GameSave $gameSave)
    {
        $this->authorizeGameSave('update', $gameSave);

        $data = $request->validated();

        $teamId = (int) $data['team_id'];
        if ($refused = $this->refuseUnlessActiveTeam($gameSave, $teamId)) return $refused;
        $team   = $gameSave->gameTeams()->where('id', $teamId)->firstOrFail();

        $team->formation = $data['formation'];
        $team->save();

        return back()->with('success', 'Formation mise à jour.');
    }

    /**
     * Substitution : échange un titulaire avec un remplaçant.
     * - swap is_starter sur les deux contrats
     * - réassigne le slot du sortant vers le rentrant
     * Atomique en transaction.
     */
    public function substitute(SubstitutePlayerRequest $request, GameSave $gameSave)
    {
        $this->authorizeGameSave('update', $gameSave);

        $data = $request->validated();

        $teamId       = (int) $data['team_id'];
        $starterId    = (int) $data['starter_id'];
        $substituteId = (int) $data['substitute_id'];

        if ($starterId === $substituteId) return back();

        // Verrou de save (qui ouvre aussi la transaction) : state.lineup est relu
        // à jour avant d'être réécrit.
        return $this->withSaveLock($gameSave, function (GameSave $gameSave) use ($teamId, $starterId, $substituteId) {
            if ($refused = $this->refuseUnlessActiveTeam($gameSave, $teamId)) return $refused;

            $team = $gameSave->gameTeams()->where('id', $teamId)->firstOrFail();

            // 1. Swap is_starter (deux UPDATE explicites pour portabilité MySQL/PG)
            GameContract::where('game_team_id', $team->id)
                ->where('game_player_id', $starterId)
                ->update(['is_starter' => false]);

            GameContract::where('game_team_id', $team->id)
                ->where('game_player_id', $substituteId)
                ->update(['is_starter' => true]);

            // 2. Réassigner le slot du sortant vers le rentrant
            $gameSave->gameState()->replaceInLineup($team->id, $starterId, $substituteId);
            $gameSave->save();

            return back()->with('success', 'Substitution effectuée.');
        });
    }

    /**
     * Seule l'équipe qui a la main (siège actif) peut changer sa composition :
     * modifier celle d'une équipe IA ou d'un autre manager humain revenait à
     * consigner gratuitement ses titulaires (et à saboter un joueur en hot-seat).
     */
    private function refuseUnlessActiveTeam(GameSave $gameSave, int $teamId): ?\Illuminate\Http\RedirectResponse
    {
        // Son équipe, ou une équipe IA avec l'option « Accès aux autres clubs ».
        if ($gameSave->canManageTeam($teamId)) {
            return null;
        }

        return back()->withErrors(['team_id' => "La composition d'une autre équipe ne peut pas être modifiée."]);
    }
}
