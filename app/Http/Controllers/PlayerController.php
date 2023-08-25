<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePlayerRequest;
use App\Http\Requests\UpdatePlayerRequest;
use App\Models\Player;
use Inertia\Inertia;

class PlayerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Inertia::render('Players/Index', [
            'players' => Player::all(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePlayerRequest $request)
    {
        $player = Player::create($request->validated());
        return response()->json([
            'message' => 'Player created successfully.',
            'player' => $player
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Player $player)
    {
        return response()->json($player);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePlayerRequest $request, Player $player)
    {
        $player->update($request->validated());
        return response()->json([
            'message' => 'Player updated successfully.',
            'player' => $player
        ]);
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
