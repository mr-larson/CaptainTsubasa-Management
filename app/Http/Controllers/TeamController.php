<?php

namespace App\Http\Controllers;

use App\Http\Requests\TeamRequest;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Str;

/** Gestion des équipes de la base de données (équipes « canon ») : liste, création, modification, suppression et logo. */
class TeamController extends Controller
{
    /**
     * Écran unique des équipes de la base : liste + fiche, création comprise
     * (fiche vide sur la même page).
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Team::class);

        return Inertia::render('Teams/Index', [
            'teams'       => Team::orderBy('name')->get(),
            // Équipe à ouvrir (ex. juste après sa création) / fiche vide demandée.
            'selectedId'  => $request->integer('selected') ?: null,
            'startCreate' => $request->boolean('create'),
        ]);
    }

    /** Ancienne URL de création : ouvre l'écran en mode création. */
    public function create(): RedirectResponse
    {
        return redirect()->route('teams.index', ['create' => 1]);
    }

    /**
     * Enregistrement d’une nouvelle équipe.
     */
    public function store(TeamRequest $request): RedirectResponse
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

        return redirect()
            ->route('teams.index', ['selected' => $team->id])
            ->with('success', 'Équipe créée.');
    }

    /** Ancienne URL d'édition : même écran que la liste. */
    public function edit(Request $request): Response
    {
        return $this->index($request);
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

        return back()->with('success', 'Équipe mise à jour.');
    }

    /**
     * Suppression d’une équipe.
     */
    public function destroy(Team $team): RedirectResponse
    {
        $this->authorize('delete', $team);

        $team->delete();

        return redirect()
            ->route('teams.index')
            ->with('success', 'Équipe supprimée.');
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
