<?php

namespace App\Http\Controllers;

use App\Enums\PlayerPosition;
use App\Http\Requests\PlayerRequest;
use App\Models\Player;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;


class PlayerController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Players/Edit', [
            'players'        => Player::orderBy('firstname')->get(),
            'positions'      => PlayerPosition::values(),
            'positionLabels' => PlayerPosition::labels(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Players/Create', [
            'positions'      => PlayerPosition::values(),
            'positionLabels' => PlayerPosition::labels(),
        ]);
    }

    public function store(PlayerRequest $request): RedirectResponse
    {
        $data = $request->validated();

        // ✅ UPLOAD PHOTO (si présente)
        if ($request->hasFile('photo')) {
            // stock dans storage/app/public/players/...
            $data['photo_path'] = $request->file('photo')->store('players', 'public');
        }

        Player::create($data);

        return redirect()
            ->route('players.index')
            ->with('success', 'Le joueur a été créé avec succès.');
    }

    public function edit(): Response
    {
        return Inertia::render('Players/Edit', [
            'players'        => Player::orderBy('firstname')->get(),
            'positions'      => PlayerPosition::values(),
            'positionLabels' => PlayerPosition::labels(),
        ]);
    }

    public function update(PlayerRequest $request, Player $player): RedirectResponse
    {
        $data = $request->validated();

        // Nouvelle photo : l'ancienne est conservée, car les saves en copient le
        // chemin (game_players.photo_path) ; l'effacer casserait leur affichage.
        if ($request->hasFile('photo')) {
            $data['photo_path'] = $request->file('photo')->store('players', 'public');
        }

        $player->update($data);

        return redirect()
            ->route('players.index')
            ->with('success', 'Le joueur a été mis à jour avec succès.');
    }

    public function destroy(Player $player): RedirectResponse
    {
        // Photo conservée (suppression douce, et les saves en copient le chemin).
        $player->delete();

        return redirect()
            ->route('players.index')
            ->with('success', 'Le joueur a été supprimé avec succès.');
    }
}
