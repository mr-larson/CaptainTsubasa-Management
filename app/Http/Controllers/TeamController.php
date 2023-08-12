<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTeamRequest;
use App\Http\Requests\UpdateTeamRequest;
use App\Models\Team;

class TeamController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $teams = Team::all();
        return response()->json($teams);
    }

    /**
     * Show the form for creating a new resource.
     * NOTE: Avec Vue.js, cela n'est généralement pas nécessaire, car le formulaire serait côté client.
     */
    public function create()
    {
        // Return view or redirect to Vue.js route
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreTeamRequest $request)
    {
        $team = Team::create($request->validated());

        return response()->json([
            'message' => 'Team created successfully.',
            'team' => $team
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Team $team)
    {
        return response()->json($team);
    }

    /**
     * Show the form for editing the specified resource.
     * NOTE: Avec Vue.js, cela n'est généralement pas nécessaire, car le formulaire serait côté client.
     */
    public function edit(Team $team)
    {
        // Return view or redirect to Vue.js route
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateTeamRequest $request, Team $team)
    {
        $team->update($request->validated());

        return response()->json([
            'message' => 'Team updated successfully.',
            'team' => $team
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Team $team)
    {
        $team->delete();

        return response()->json([
            'message' => 'Team deleted successfully.'
        ]);
    }
}
