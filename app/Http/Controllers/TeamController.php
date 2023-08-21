<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTeamRequest;
use App\Http\Requests\UpdateTeamRequest;
use App\Models\Team;
use Inertia\Inertia;

class TeamController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): \Illuminate\Http\JsonResponse
    {
        $teams = Team::all();
        return response()->json($teams);
    }

    // app/Http/Controllers/TeamController.php

    public function create(): \Inertia\Response
    {
        return Inertia::render('CreateTeam');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreTeamRequest $request): \Illuminate\Http\JsonResponse
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
    public function show(Team $team): \Illuminate\Http\JsonResponse
    {
        return response()->json($team);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Team $team)
    {
        $allTeams = Team::all();

        return Inertia::render('Teams/Edit', [
            'team' => $team->only('id', 'name', 'logo_path', 'budget'),
            'allTeams' => $allTeams
        ]);
    }



    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateTeamRequest $request, Team $team)
    {
        $team->update($request->validated());
        return Inertia::render('Teams/Edit', [
            'team' => $team->only('id', 'name', 'logo_path', 'budget'),
            'allTeams' => Team::all()
        ])->with('success', 'Team updated successfully.');
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
