<?php

namespace App\Http\Controllers\GameSaves;

use App\Http\Controllers\Controller;
use App\Http\Requests\GameTeamRequest;
use App\Models\GameSaves\GameSave;
use App\Models\GameSaves\GameTeam;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
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
     * Formulaire d'édition.
     */
    public function edit(Request $request, GameSave $gameSave, GameTeam $team): Response
    {
        if ($gameSave->user_id !== $request->user()->id) abort(403);
        if ($team->game_save_id !== $gameSave->id) abort(403);

        return Inertia::render('GameSaves/GameTeams/Edit', [
            'gameSave' => $gameSave,
            'team'     => $team,
        ]);
    }

    /**
     * Mise à jour d'une équipe.
     */
    public function update(GameTeamRequest $request, GameSave $gameSave, GameTeam $team)
    {
        if ($gameSave->user_id !== $request->user()->id) abort(403);
        if ($team->game_save_id !== $gameSave->id) abort(403);

        $data = $request->validated();

        // gestion suppression logo
        if ($request->boolean('remove_logo') && $team->logo_path) {
            $this->deleteGameTeamLogo($team->logo_path);
            $team->logo_path = null;
        }

        // upload logo
        if ($request->hasFile('logo')) {
            if ($team->logo_path) {
                $this->deleteGameTeamLogo($team->logo_path);
            }

            $team->logo_path = $this->storeGameTeamLogo(
                $request->file('logo'),
                $data['name'],
                $gameSave->id
            );
        }

        // données textuelles
        $team->fill($request->safe()->except(['logo', 'remove_logo']));
        $team->save();

        return back()->with('success', 'Équipe mise à jour.');
    }

    /**
     * Formulaire création équipe.
     */
    public function create(Request $request, GameSave $gameSave): Response
    {
        if ($gameSave->user_id !== $request->user()->id) abort(403);

        return Inertia::render('GameSaves/GameTeams/Create', [
            'gameSave' => $gameSave,
        ]);
    }

    /**
     * Création d'une équipe.
     */
    public function store(GameTeamRequest $request, GameSave $gameSave)
    {
        if ($gameSave->user_id !== $request->user()->id) abort(403);

        $data = $request->validated();

        $team = new GameTeam();
        $team->game_save_id = $gameSave->id;
        $team->fill($request->safe()->except(['logo']));

        // upload éventuel
        if ($request->hasFile('logo')) {
            $team->logo_path = $this->storeGameTeamLogo(
                $request->file('logo'),
                $data['name'],
                $gameSave->id
            );
        }

        $team->wins = 0;
        $team->draws = 0;
        $team->losses = 0;

        $team->save();

        return redirect()->route('game-saves.teams.index', [
            'gameSave' => $gameSave->id,
        ]);
    }

    /**
     * Suppression d'une équipe.
     */
    public function destroy(Request $request, GameSave $gameSave, GameTeam $team)
    {
        if ($gameSave->user_id !== $request->user()->id) abort(403);
        if ($team->game_save_id !== $gameSave->id) abort(403);

        if ($team->logo_path) {
            $this->deleteGameTeamLogo($team->logo_path);
        }

        $team->delete();

        return redirect()->route('game-saves.teams.index', [
            'gameSave' => $gameSave->id,
        ])->with('success', 'Équipe supprimée.');
    }

    /**
     * Stockage logo.
     */
    private function storeGameTeamLogo(UploadedFile $file, string $teamName, int $gameSaveId): string
    {
        $dir = public_path("images/game-teams/{$gameSaveId}");
        if (!File::exists($dir)) File::makeDirectory($dir, 0755, true);

        $filename = Str::slug($teamName) . '-' . Str::random(6) . '.' . $file->getClientOriginalExtension();
        $file->move($dir, $filename);

        return "images/game-teams/{$gameSaveId}/{$filename}";
    }

    /**
     * Suppression logo.
     */
    private function deleteGameTeamLogo(string $path): void
    {
        $absolute = public_path($path);
        if (File::exists($absolute)) File::delete($absolute);
    }
}
