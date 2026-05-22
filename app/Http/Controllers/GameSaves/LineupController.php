<?php

namespace App\Http\Controllers\GameSaves;

use App\Http\Controllers\Controller;
use App\Models\GameSaves\GameContract;
use App\Models\GameSaves\GameSave;
use Illuminate\Http\Request;

class LineupController extends Controller
{
    /**
     * Toggle titulaire / remplaçant sur un contrat.
     */
    public function toggleStarter(Request $request, GameContract $contract)
    {
        $gameSave = $contract->gameSave;

        if ($gameSave->user_id !== $request->user()->id) {
            abort(403);
        }

        $contract->is_starter = ! $contract->is_starter;
        $contract->save();

        return back()->with('success', 'Changement effectué');
    }

    /**
     * Sauvegarde la composition (slots) ET la formation d'une équipe.
     */
    public function update(Request $request, GameSave $gameSave)
    {
        if ($gameSave->user_id !== $request->user()->id) {
            abort(403);
        }

        $data = $request->validate([
            'team_id'   => ['required', 'integer'],
            'slots'     => ['required', 'array', 'size:11'],
            'slots.*.slot'      => ['required', 'integer', 'between:1,11'],
            'slots.*.player_id' => ['nullable', 'integer'],
            // Formation optionnelle — si absente on garde la valeur existante
            'formation' => ['nullable', 'string', 'in:3-2-3-2,3-3-2-2,3-2-2-3,3-4-2-1,3-1-3-3,4-2-2-2,4-3-2-1,4-1-3-2,4-3-1-2,4-2-1-3,5-2-2-1,5-3-1-1,5-2-1-2,5-1-2-2,5-2-3-0'],
        ]);

        $teamId = (int) $data['team_id'];

        // Vérifier que l'équipe appartient à cette save
        $team = $gameSave->gameTeams()
            ->where('id', $teamId)
            ->firstOrFail();

        // Normalise le mapping slot → player_id
        $slots = [];
        foreach ($data['slots'] as $row) {
            $slot = (int) $row['slot'];
            $pid  = $row['player_id'] ? (int) $row['player_id'] : null;
            $slots[$slot] = $pid;
        }

        $state = $gameSave->state ?? [];

        // --- Lineup (slots) ---
        $state['lineup']                  = $state['lineup'] ?? [];
        $state['lineup'][$team->id]       = $state['lineup'][$team->id] ?? [];
        $state['lineup'][$team->id]['slots'] = $slots;

        // --- Formation ---
        if (!empty($data['formation'])) {
            $state['lineup'][$team->id]['formation'] = $data['formation'];
        }

        $gameSave->state = $state;
        $gameSave->save();

        return back()->with('success', 'Composition mise à jour.');
    }

    /**
     * Sauvegarde uniquement la formation d'une équipe (sans toucher aux slots).
     * Appelé quand l'utilisateur change de formation dans le sélecteur.
     */
    public function updateFormation(Request $request, GameSave $gameSave)
    {
        if ($gameSave->user_id !== $request->user()->id) {
            abort(403);
        }

        $data = $request->validate([
            'team_id'   => ['required', 'integer'],
            'formation' => ['required', 'string', 'in:3-2-3-2,3-3-2-2,3-2-2-3,3-4-2-1,3-1-3-3,4-2-2-2,4-3-2-1,4-1-3-2,4-3-1-2,4-2-1-3,5-2-2-1,5-3-1-1,5-2-1-2,5-1-2-2'],
        ]);

        $teamId = (int) $data['team_id'];

        $gameSave->gameTeams()->where('id', $teamId)->firstOrFail();

        $state = $gameSave->state ?? [];
        $state['lineup']                           = $state['lineup'] ?? [];
        $state['lineup'][$teamId]                  = $state['lineup'][$teamId] ?? [];
        $state['lineup'][$teamId]['formation']     = $data['formation'];

        $gameSave->state = $state;
        $gameSave->save();

        return back()->with('success', 'Formation mise à jour.');
    }
}
