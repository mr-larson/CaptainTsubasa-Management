<?php

namespace App\Http\Controllers\GameManagement;

use App\Http\Controllers\Controller;
use App\Models\GameSave;
use App\Models\GameTeam;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class GameTeamController extends Controller
{
    /**
     * Liste des équipes d'une sauvegarde.
     */
    public function index(Request $request, GameSave $gameSave): Response
    {
        // sécurité ownership
        if ($gameSave->user_id !== $request->user()->id) {
            abort(403);
        }

        $teams = GameTeam::where('game_save_id', $gameSave->id)
            ->orderBy('name')
            ->get();

        return Inertia::render('GameSaves/GameTeams/Index', [
            'gameSave' => $gameSave,
            'teams'    => $teams,
        ]);


    }

    /**
     * Affichage du formulaire d'édition.
     */
    public function edit(Request $request, GameSave $gameSave, GameTeam $team): Response
    {
        if ($gameSave->user_id !== $request->user()->id) {
            abort(403);
        }

        if ($team->game_save_id !== $gameSave->id) {
            abort(403);
        }

        return Inertia::render('GameSaves/GameTeams/Edit', [
            'gameSave' => $gameSave,
            'team'     => $team,
        ]);

    }

    /**
     * Mise à jour d'une équipe de partie.
     */
    public function update(Request $request, GameSave $gameSave, GameTeam $team)
    {
        if ($gameSave->user_id !== $request->user()->id) {
            abort(403);
        }

        if ($team->game_save_id !== $gameSave->id) {
            abort(403);
        }

        $data = $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'budget'      => ['required', 'integer', 'min:0'],
            'remove_logo' => ['boolean'],
            'logo'        => ['nullable', 'image'],
        ]);

        $removeLogo = $request->boolean('remove_logo');
        unset($data['logo'], $data['remove_logo']);

        $team->fill($data);

        // suppression du logo si demandé
        if ($removeLogo && $team->logo_path) {
            $this->deleteGameTeamLogo($team->logo_path);
            $team->logo_path = null;
        }

        // upload d'un nouveau logo
        if ($request->hasFile('logo')) {
            if ($team->logo_path) {
                $this->deleteGameTeamLogo($team->logo_path);
            }

            $team->logo_path = $this->storeGameTeamLogo(
                $request->file('logo'),
                $team->name,
                $gameSave->id
            );
        }

        $team->save();

        return back()->with('success', 'Équipe mise à jour.');
    }

    /**
     * Stockage du logo dans un répertoire dédié par partie.
     */
    private function storeGameTeamLogo(\Illuminate\Http\UploadedFile $file, string $teamName, int $gameSaveId): string
    {
        $dir = public_path("images/game-teams/{$gameSaveId}");

        if (!File::exists($dir)) {
            File::makeDirectory($dir, 0755, true);
        }

        $ext = $file->getClientOriginalExtension();
        $base = Str::slug($teamName);
        $filename = $base . '-' . Str::random(6) . '.' . $ext;

        $file->move($dir, $filename);

        return "images/game-teams/{$gameSaveId}/{$filename}";
    }

    /**
     * Suppression du logo.
     */
    private function deleteGameTeamLogo(string $path): void
    {
        $absolute = public_path($path);

        if (File::exists($absolute)) {
            File::delete($absolute);
        }
    }

    /**
     * Formulaire de création d'équipe dans la sauvegarde.
     */
    public function create(Request $request, GameSave $gameSave): Response
    {
        // sécurité ownership
        if ($gameSave->user_id !== $request->user()->id) {
            abort(403);
        }

        return Inertia::render('GameSaves/GameTeams/Create', [
            'gameSave' => $gameSave,
        ]);
    }

    /**
     * Création d'une équipe dans une sauvegarde.
     */
    public function store(Request $request, GameSave $gameSave)
    {
        // sécurité ownership
        if ($gameSave->user_id !== $request->user()->id) {
            abort(403);
        }

        // Validation
        $data = $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'budget'      => ['required', 'integer', 'min:0'],
            'logo'        => ['nullable', 'image'],
        ]);

        // Création du modèle
        $team = new GameTeam();
        $team->game_save_id = $gameSave->id;
        $team->name         = $data['name'];
        $team->description  = $data['description'] ?? null;
        $team->budget       = $data['budget'];
        $team->wins         = 0;
        $team->draws        = 0;
        $team->losses       = 0;

        // Upload éventuel du logo
        if ($request->hasFile('logo')) {
            $team->logo_path = $this->storeGameTeamLogo(
                $request->file('logo'),
                $team->name,
                $gameSave->id
            );
        }

        $team->save();

        return redirect()->route('game-saves.teams.index', $gameSave->id);
    }

    /**
     * Suppression d'une équipe d'une sauvegarde.
     */
    public function destroy(Request $request, GameSave $gameSave, GameTeam $team)
    {
        // sécurité ownership (sauvegarde)
        if ($gameSave->user_id !== $request->user()->id) {
            abort(403);
        }

        // sécurité : l'équipe doit appartenir à cette sauvegarde
        if ($team->game_save_id !== $gameSave->id) {
            abort(403);
        }

        // supprimer le logo si existe
        if ($team->logo_path) {
            $this->deleteGameTeamLogo($team->logo_path);
        }

        // supprimer l'équipe
        $team->delete();

        // redirection vers l'index des équipes
        return redirect()->route('game-saves.teams.index', [
            'gameSave' => $gameSave->id,
        ])->with('success', 'Équipe supprimée.');
    }
}
