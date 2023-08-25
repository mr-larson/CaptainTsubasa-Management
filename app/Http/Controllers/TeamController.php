<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTeamRequest;
use App\Http\Requests\UpdateTeamRequest;
use App\Models\Team;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TeamController extends Controller
{
    public function index()
    {
        $teams = Team::orderBy('name')->get();
        return Inertia::render('Teams/Index', [
            'teams' => $teams,
        ]);
    }

    public function create()
    {
        return Inertia::render('Teams/Create');
    }

    public function store(StoreTeamRequest $request)
    {
        Team::create($request->validated());
        return redirect()->route('teams');
    }

    public function show(Team $team)
    {
        return response()->json($team);
    }

    public function edit(Request $request)
    {
        return response()->json([
            'team' => Team::find($request->id),
        ]);
    }

    public function update(UpdateTeamRequest $request, Team $team)
    {
        $team->update($request->validated());
        return redirect()->route('teams');
    }


    public function destroy(Team $team)
    {
        $team->delete();
        return response()->json([
            'message' => 'Team deleted successfully.',
        ]);
    }

}
