<?php

namespace App\Http\Controllers\GameSaves;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\AuthorizesGameSave;
use App\Http\Requests\GameSaves\GameTeamRequest;
use App\Models\GameSaves\GameSave;
use App\Models\GameSaves\GameTeam;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class GameTeamController extends Controller
{
    use AuthorizesGameSave;

    /**
     * Liste des équipes d'une sauvegarde.
     */
    public function index(Request $request, GameSave $gameSave): Response
    {
        $this->authorizeGameSave('view', $gameSave);

        $teams = GameTeam::where('game_save_id', $gameSave->id)
            ->orderBy('name')
            ->get();

        return Inertia::render('GameSaves/GameTeams/Index', [
            'gameSave' => $gameSave,
            'teams'    => $teams,
        ]);
    }

    /**
     * Ancienne URL d'édition : l'édition se fait dans la page liste
     * (GameTeams/Index) ; la page GameTeams/Edit n'existe pas.
     */
    public function edit(Request $request, GameSave $gameSave, GameTeam $team): RedirectResponse
    {
        $this->authorizeGameSave('view', $gameSave, $team);

        return redirect()->route('game-saves.teams.index', $gameSave);
    }

    /**
     * Mise à jour d'une équipe.
     */
    public function update(GameTeamRequest $request, GameSave $gameSave, GameTeam $team)
    {
        $this->authorizeGameSave('update', $gameSave, $team);

        $data = $request->validated();

        // gestion suppression logo
        if ($request->boolean('remove_logo') && $team->logo_path) {
            $this->deleteGameTeamLogo($team->logo_path, $gameSave->id);
            $team->logo_path = null;
        }

        // upload logo
        if ($request->hasFile('logo')) {
            if ($team->logo_path) {
                $this->deleteGameTeamLogo($team->logo_path, $gameSave->id);
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
        $this->authorizeGameSave('view', $gameSave);

        return Inertia::render('GameSaves/GameTeams/Create', [
            'gameSave' => $gameSave,
        ]);
    }

    /**
     * Création d'une équipe.
     */
    public function store(GameTeamRequest $request, GameSave $gameSave)
    {
        $this->authorizeGameSave('update', $gameSave);

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
        $this->authorizeGameSave('update', $gameSave, $team);

        if ($team->logo_path) {
            $this->deleteGameTeamLogo($team->logo_path, $gameSave->id);
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
        // Extension déduite du contenu (jamais du nom client : « logo.html » servi en text/html).
        $filename = Str::slug($teamName) . '-' . Str::random(6) . '.' . $file->guessExtension();
        // Disque public (servi sous /storage, comme l'affiche le front) — et non
        // public/images, d'où les logos uploadés n'étaient jamais affichés.
        return $file->storeAs("images/game-teams/{$gameSaveId}", $filename, 'public');
    }

    /**
     * Suppression logo — uniquement les uploads propres à cette save : les
     * logos canon sont partagés par toutes les saves.
     */
    private function deleteGameTeamLogo(string $path, int $gameSaveId): void
    {
        if (! str_starts_with($path, "images/game-teams/{$gameSaveId}/")) {
            return;
        }

        Storage::disk('public')->delete($path);
    }
}
