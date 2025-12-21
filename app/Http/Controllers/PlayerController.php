<?php

namespace App\Http\Controllers;

use App\Enums\PlayerPosition;
use App\Http\Requests\PlayerRequest;
use App\Models\Player;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;


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

        // ✅ UPLOAD PHOTO (si présente) + suppression ancienne
        if ($request->hasFile('photo')) {
            if ($player->photo_path) {
                Storage::disk('public')->delete($player->photo_path);
            }

            $data['photo_path'] = $request->file('photo')->store('players', 'public');
        }

        $player->update($data);

        return redirect()
            ->route('players.index')
            ->with('success', 'Le joueur a été mis à jour avec succès.');
    }

    public function destroy(Player $player): RedirectResponse
    {
        // ✅ supprimer aussi la photo
        if ($player->photo_path) {
            Storage::disk('public')->delete($player->photo_path);
        }

        $player->delete();

        return redirect()
            ->route('players.index')
            ->with('success', 'Le joueur a été supprimé avec succès.');
    }
}
