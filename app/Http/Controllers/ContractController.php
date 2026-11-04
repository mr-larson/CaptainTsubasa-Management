<?php

namespace App\Http\Controllers;

use App\Http\Requests\ContractRequest;
use App\Models\Contract;
use App\Models\Player;
use App\Models\Team;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ContractController extends Controller
{
    /**
     * Écran unique des contrats de la base : liste + fiche, création comprise
     * (fiche vide sur la même page).
     */
    public function index(Request $request): Response
    {
        $contracts = Contract::with(['team', 'player'])
            ->get()
            ->map(fn (Contract $c) => [
                'id'                => $c->id,
                'team_id'           => $c->team_id,
                'player_id'         => $c->player_id,
                'team_name'         => $c->team?->name,
                'team_logo_path'    => $c->team?->logo_path,
                'player_name'       => $c->player ? trim(($c->player->firstname ?? '').' '.($c->player->lastname ?? '')) : null,
                'salary'            => $c->salary,
                'matches_total'     => $c->matches_total,
                'matches_played'    => $c->matches_played,
                'matches_remaining' => $c->matches_remaining,
            ])
            ->sortBy(fn ($c) => mb_strtolower($c['player_name'] ?? ''))
            ->values();

        return Inertia::render('Contracts/Index', [
            'contracts'   => $contracts,
            'teams'       => Team::orderBy('name')->get(['id', 'name']),
            'players'     => Player::orderBy('firstname')->get(['id', 'firstname', 'lastname']),
            // Contrat à ouvrir (ex. juste après sa création) / fiche vide demandée.
            'selectedId'  => $request->integer('selected') ?: null,
            'startCreate' => $request->boolean('create'),
        ]);
    }

    /** Ancienne URL de création : ouvre l'écran en mode création. */
    public function create(): RedirectResponse
    {
        return redirect()->route('contracts.index', ['create' => 1]);
    }

    public function store(ContractRequest $request): RedirectResponse
    {
        $data = $request->validated();

        // sécurité : un contrat commence toujours avec 0 match joué
        $data['matches_played'] = 0;

        $contract = Contract::create($data);

        // Depuis l'écran Contrats : on y revient avec le contrat créé sélectionné.
        // Depuis l'onglet Contrat d'un joueur : retour à la page d'origine.
        if ($request->boolean('open_created')) {
            return redirect()
                ->route('contracts.index', ['selected' => $contract->id])
                ->with('success', 'Le contrat a été créé avec succès.');
        }

        return back()->with('success', 'Le contrat a été créé avec succès.');
    }

    /** Ancienne URL d'édition : même écran que la liste. */
    public function edit(Request $request): Response
    {
        return $this->index($request);
    }

    public function update(ContractRequest $request, Contract $contract): RedirectResponse
    {
        $data = $request->validated();

        // matches_played peut être envoyé depuis l’UI ou géré côté match engine
        $contract->update($data);

        return back()->with('success', 'Le contrat a été modifié avec succès.');
    }

    public function destroy(Contract $contract): RedirectResponse
    {
        $contract->delete();

        return back()->with('success', 'Le contrat a été supprimé.');
    }
}
