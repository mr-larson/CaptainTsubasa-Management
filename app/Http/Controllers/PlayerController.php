<?php

namespace App\Http\Controllers;

use App\Http\Requests\PlayerRequest;
use App\Models\Player;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class PlayerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Inertia::render('Players/Index', [
            'players' => Player::orderBy('name')->get()
        ]);
    }

    public function create()
    {
        return Inertia::render('Players/Create');
    }
    /**
     * Store a newly created resource in storage.
     */
    public function store(PlayerRequest $request)
    {
        if($request->hasFile('image_path')) {
            $path = $request->file('image_path')->store('players', 'public');
            $request->merge(['image_path' => $path]);
        }

        Player::create($request->all());

        return redirect()->route('players')->with('success', "Le joueur a été créé avec succès");
    }

    public function edit()
    {
        return Inertia::render('Players/Edit', [
            'players' => Player::orderBy('name')->get()
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(PlayerRequest $request, Player $player)
    {

        $player->update($request->all());

        return redirect()->route('players')->with('success', "Le joueur a été mis à jour avec succès");
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Player $player)
    {
        $player->delete();

        return response()->json([
            'message' => 'Player deleted successfully.'
        ]);
    }
}
