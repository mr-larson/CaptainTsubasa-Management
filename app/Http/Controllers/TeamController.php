<?php

namespace App\Http\Controllers;

use App\Http\Requests\TeamRequest;
use App\Http\Resources\TeamResource;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

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
    public function store(TeamRequest $request): RedirectResponse
    {
        Team::create($request->validated());

        return redirect()
            ->route('teams.edit')
            ->with('success', "L'équipe a été créée avec succès");
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
    public function update(TeamRequest $request, Team $team): RedirectResponse
    {
        $team->update($request->validated());

        return redirect()
            ->back()
            ->with('success', 'Équipe mise à jour avec succès.');
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
}
