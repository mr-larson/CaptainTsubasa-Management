<?php
namespace App\Http\Controllers\GameSaves;

use App\Http\Controllers\Controller;
use App\Models\GameSaves\GameContract;
use App\Models\GameSaves\GameSave;
use App\Models\GameSaves\GameTeam;
use Illuminate\Http\Request;

class LineupController extends Controller
{
    /**
     * Toggle titulaire / remplaçant sur un contrat.
     */
    public function toggleStarter(Request $request, GameContract $contract)
    {
        $gameSave = $contract->gameSave;
        if ($gameSave->user_id !== $request->user()->id) abort(403);

        $contract->is_starter = !$contract->is_starter;
        $contract->save();
        return back()->with('success', 'Changement effectué');
    }

    /**
     * Toggle capitaine sur un contrat.
     * Un seul capitaine par équipe : désigne le nouveau et retire l'ancien.
     */
    public function toggleCaptain(Request $request, GameContract $contract)
    {
        $gameSave = $contract->gameSave;
        if ($gameSave->user_id !== $request->user()->id) abort(403);

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
    }

    /**
     * Sauvegarde la composition (slots) ET la formation d'une équipe.
     */
    public function update(Request $request, GameSave $gameSave)
    {
        if ($gameSave->user_id !== $request->user()->id) abort(403);

        $data = $request->validate([
            'team_id'           => ['required', 'integer'],
            'slots'             => ['required', 'array', 'size:11'],
            'slots.*.slot'      => ['required', 'integer', 'between:1,11'],
            'slots.*.player_id' => ['nullable', 'integer'],
            'formation'         => ['nullable', 'string', 'in:3-2-3-2,3-3-2-2,3-2-2-3,3-4-2-1,3-1-3-3,4-2-2-2,4-3-2-1,4-1-3-2,4-3-1-2,4-2-1-3,5-2-2-1,5-3-1-1,5-2-1-2,5-1-2-2,5-2-3-0'],
        ]);

        $teamId = (int) $data['team_id'];
        $team   = $gameSave->gameTeams()->where('id', $teamId)->firstOrFail();

        $slots = [];
        foreach ($data['slots'] as $row) {
            $slots[(int) $row['slot']] = $row['player_id'] ? (int) $row['player_id'] : null;
        }

        $state = $gameSave->state ?? [];
        $state['lineup']                     = $state['lineup'] ?? [];
        $state['lineup'][$team->id]          = $state['lineup'][$team->id] ?? [];
        $state['lineup'][$team->id]['slots'] = $slots;
        $gameSave->state = $state;
        $gameSave->save();

        if (!empty($data['formation'])) {
            $team->formation = $data['formation'];
            $team->save();
        }

        return back()->with('success', 'Composition mise à jour.');
    }

    /**
     * Sauvegarde uniquement la formation d'une équipe.
     */
    public function updateFormation(Request $request, GameSave $gameSave)
    {
        if ($gameSave->user_id !== $request->user()->id) abort(403);

        $data = $request->validate([
            'team_id'   => ['required', 'integer'],
            'formation' => ['required', 'string', 'in:3-2-3-2,3-3-2-2,3-2-2-3,3-4-2-1,3-1-3-3,4-2-2-2,4-3-2-1,4-1-3-2,4-3-1-2,4-2-1-3,5-2-2-1,5-3-1-1,5-2-1-2,5-1-2-2'],
        ]);

        $teamId = (int) $data['team_id'];
        $team   = $gameSave->gameTeams()->where('id', $teamId)->firstOrFail();

        $team->formation = $data['formation'];
        $team->save();

        return back()->with('success', 'Formation mise à jour.');
    }
}
