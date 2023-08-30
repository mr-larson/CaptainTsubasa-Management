<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTeamRequest;
use App\Http\Requests\UpdateTeamRequest;
use App\Models\Team;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
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
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('teams', 'public');
            $request->merge(['image' => $path]);
        }

        Team::create($request->all());

        return redirect()->route('teams')->with('success', "L'équipe a été créée avec succès");
    }


    public function show(Team $team)
    {
        return response()->json($team);
    }

    public function edit(Request $request)
    {
        $team = Team::find($request->id)->append('image');
        return response()->json(['team' => $team]);
    }


    public function update(StoreTeamRequest $request, Team $team)
    {
        if ($request->hasFile('image')) {
            Storage::disk('public')->delete($team->image);
            $path = $request->file('image')->store('teams', 'public');
            $request->merge(['image' => $path]);
        }

        $team->update($request->all());

        return redirect()->route('teams')->with('success', "L'équipe a été mise à jour avec succès");
    }


    public function destroy(Team $team)
    {
        $team->delete();
        return redirect()->route('teams')->with('message', 'Team successfully deleted.');
    }


}
