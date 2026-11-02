<?php

namespace App\Http\Controllers;

use App\Http\Requests\TeamRequest;
use App\Http\Resources\TeamResource;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Str;

class TeamController extends Controller
{
    /**
     * Liste des équipes (vue "Index").
     */
    public function index(): Response
    {
        return Inertia::render('Teams/Index', [
            'teams' => TeamResource::collection(
                Team::orderBy('name')->get()
            ),
        ]);
    }

    /**
     * Formulaire de création d’équipe.
     */
    public function create(): Response
    {
        return Inertia::render('Teams/Create');
    }

    /**
     * Enregistrement d’une nouvelle équipe.
     */
    // App\Http\Controllers\TeamController.php

    public function store(TeamRequest $request)
    {
        $data = $request->validated();

        $team = new Team();

        // On ne "fill" pas logo/remove_logo
        unset($data['remove_logo'], $data['logo']);
        $team->fill($data);

        if ($request->hasFile('logo')) {
            $team->logo_path = $this->storeTeamLogo(
                $request->file('logo'),
                $team->name
            );
        }

        $team->save();

        return redirect()->back();
    }

    /**
     * Écran d’édition (avec liste dans la sidebar).
     */
    public function edit(): Response
    {
        return Inertia::render('Teams/Edit', [
            'teams' => Team::orderBy('name')->get(),
        ]);
    }

    /**
     * Mise à jour d’une équipe.
     */
    public function update(TeamRequest $request, Team $team)
    {
        $data = $request->validated();

        // ✅ IMPORTANT : on lit remove_logo AVANT de unset
        $removeLogo = $request->boolean('remove_logo');

        unset($data['remove_logo'], $data['logo']);
        $team->fill($data);

        // Retrait explicite du logo (le fichier est conservé, cf. storeTeamLogo).
        if ($removeLogo) {
            $team->logo_path = null;
        }

        // remplacement logo
        if ($request->hasFile('logo')) {
            $team->logo_path = $this->storeTeamLogo(
                $request->file('logo'),
                $team->name
            );
        }

        $team->save();

        return redirect()->back();
    }

    /**
     * Suppression d’une équipe.
     */
    public function destroy(Team $team): RedirectResponse
    {
        $team->delete();

        return redirect()
            ->route('teams.edit')
            ->with('message', 'Équipe supprimée avec succès.');
    }

    /**
     * Stocke le logo sur le disque public (servi sous /storage, comme les logos
     * canon « teams/… » affichés par le front). Un logo remplacé n'est jamais
     * supprimé : les saves en copient le chemin (game_teams.logo_path).
     */
    private function storeTeamLogo(\Illuminate\Http\UploadedFile $file, string $teamName): string
    {
        // Extension déduite du contenu (pas du nom envoyé par le client).
        $filename = Str::slug($teamName).'-'.Str::random(6).'.'.$file->guessExtension();

        return $file->storeAs('teams', $filename, 'public');
    }
}
