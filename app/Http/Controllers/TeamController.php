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
        $validatedData = $request->validated();

        if ($request->hasFile('logo_path')) {
            $file = $request->file('logo_path');
            $filename = time() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('teams/logos', $filename, 'public');
            $validatedData['logo_path'] = $path;
        }

        Team::create($validatedData);
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

    public function update(StoreTeamRequest $request, Team $team)
    {
        $validatedData = $request->validated();

        if ($request->hasFile('logo_path')) {
            // Supprimer l'ancien fichier s'il existe
            if ($team->logo_path) {
                Storage::disk('public')->delete($team->logo_path);
            }

            $file = $request->file('logo_path');
            $filename = time() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('teams/logos', $filename, 'public');
            $validatedData['logo_path'] = $path;
        }

        $team->update($validatedData);
        return redirect()->route('teams');
    }

    public function destroy(Team $team)
    {
        $team->delete();
        return redirect()->route('teams')->with('message', 'Team successfully deleted.');
    }


}
