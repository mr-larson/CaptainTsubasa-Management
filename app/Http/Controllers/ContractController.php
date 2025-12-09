<?php

namespace App\Http\Controllers;

use App\Http\Requests\ContractRequest;
use App\Models\Contract;
use App\Models\Player;
use App\Models\Team;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;

class ContractController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Contracts/Index', [
            'contracts' => Contract::with(['team', 'player'])->get()->map(function ($c) {
                return [
                    'id'             => $c->id,
                    'team_id'        => $c->team_id,
                    'team_name'      => optional($c->team)->name,
                    'player_id'      => $c->player_id,
                    'player_name'    => optional($c->player)->full_name ?? optional($c->player)->firstname,
                    'salary'         => $c->salary,
                    'matches_total'  => $c->matches_total,
                    'matches_played' => $c->matches_played,
                    'matches_remaining' => $c->matches_remaining,
                ];
            }),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Contracts/Create', [
            'teams'   => Team::orderBy('name')->get(['id', 'name']),
            'players' => Player::orderBy('firstname')->get(['id', 'firstname', 'lastname']),
        ]);
    }

    public function store(ContractRequest $request): RedirectResponse
    {
        $data = $request->validated();

        // sécurité : un contrat commence toujours avec 0 match joué
        $data['matches_played'] = 0;

        Contract::create($data);

        return redirect()
            ->route('contracts.index')
            ->with('success', "Le contrat a été créé avec succès");
    }

    public function edit(): \Inertia\Response
    {
        $contracts = Contract::with(['team', 'player'])
            ->orderBy('id')
            ->get()
            ->map(function ($c) {
                $playerFullName = null;

                if ($c->player) {
                    $playerFullName = trim(
                        ($c->player->firstname ?? '') . ' ' . ($c->player->lastname ?? '')
                    );
                }

                return [
                    'id'                => $c->id,
                    'team_id'           => $c->team_id,
                    'player_id'         => $c->player_id,
                    'team_name'         => optional($c->team)->name,
                    'player_name'       => $playerFullName ?: null,
                    'salary'            => $c->salary,
                    'matches_total'     => $c->matches_total,
                    'matches_played'    => $c->matches_played,
                    'matches_remaining' => $c->matches_remaining,
                ];
            });

        return Inertia::render('Contracts/Edit', [
            'contracts' => $contracts,
            'teams'     => Team::orderBy('name')->get(['id', 'name']),
            'players'   => Player::orderBy('firstname')->get(['id', 'firstname', 'lastname']),
        ]);
    }

    public function update(ContractRequest $request, Contract $contract): RedirectResponse
    {
        $data = $request->validated();

        // matches_played peut être envoyé depuis l’UI ou géré côté match engine
        $contract->update($data);

        return redirect()
            ->route('contracts.edit')
            ->with('success', "Le contrat a été modifié avec succès");
    }

    public function destroy(Contract $contract): RedirectResponse
    {
        $contract->delete();

        return redirect()
            ->route('contracts.edit')
            ->with('message', 'Contract successfully deleted.');
    }
}
