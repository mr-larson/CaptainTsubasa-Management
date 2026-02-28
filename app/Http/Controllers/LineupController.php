<?php

namespace App\Http\Controllers;

use App\Models\GameContract;
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
}
