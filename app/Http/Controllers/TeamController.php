<?php

namespace App\Http\Controllers;

use App\Http\Requests\TeamRequest;
use App\Models\Team;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
class TeamController extends Controller
{
    public function index()
    {
        return Inertia::render('Teams/Index', [
            'teams' => Team::orderBy('name')->get()
        ]);
    }

    public function create()
    {
        return Inertia::render('Teams/Create');
    }

    public function store(TeamRequest $request)
    {
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('teams', 'public');
            $request->merge(['image' => $path]);
        }

        Team::create($request->all());

        return redirect()->route('teams')->with('success', "L'équipe a été créée avec succès");
    }

    public function edit()
    {
        return Inertia::render('Teams/Edit', [
            'teams' => Team::orderBy('name')->get()
        ]);
    }


    public function update(TeamRequest $request, Team $team)
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
