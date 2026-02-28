<?php

namespace App\Http\Controllers;

use App\Models\GameContract;
use App\Models\GameSave;
use Illuminate\Http\Request;

class LineupController extends Controller
{
    public function toggleStarter(Request $request, GameContract $contract)
    {
        $gameSave = $contract->gameSave;

        // On vérifie que cette save appartient à l'utilisateur connecté
        if ($gameSave->user_id !== $request->user()->id) {
            abort(403);
        }

        // Inversion ON/OFF
        $contract->is_starter = ! $contract->is_starter;
        $contract->save();

        return back()->with('success', 'Changement effectué');
    }

    public function update(Request $request, GameSave $gameSave)
    {
        // Vérifier ownership de la sauvegarde
        if ($gameSave->user_id !== $request->user()->id) {
            abort(403);
        }

        $data = $request->validate([
            'team_id' => ['required', 'integer'],
            'slots' => ['required', 'array', 'size:11'],
            'slots.*.slot' => ['required', 'integer', 'between:1,11'],
            'slots.*.player_id' => ['nullable', 'integer'],
        ]);

        $teamId = (int)$data['team_id'];

        // Vérifier que l'équipe appartient bien à cette save
        $team = $gameSave->gameTeams()
            ->where('id', $teamId)
            ->firstOrFail();

        // Normalise le mapping slot -> player_id (ou null)
        $slots = [];
        foreach ($data['slots'] as $row) {
            $slot = (int)$row['slot'];
            $pid = $row['player_id'] ? (int)$row['player_id'] : null;
            $slots[$slot] = $pid;
        }

        $state = $gameSave->state ?? [];
        $state['lineup'] = $state['lineup'] ?? [];
        $state['lineup'][$team->id] = [
            'slots' => $slots,
        ];

        $gameSave->state = $state;
        $gameSave->save();

        return back()->with('success', 'Composition mise à jour.');
    }
}
