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
        $team = new Team();

        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('public/images/teams');
            $team->logo_path = Storage::url($path);
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
        if ($request->hasFile('logo')) {
            // Supprimez l'ancienne logo si elle existe
            if ($team->logo_path && Storage::exists($team->logo_path)) {
                Storage::delete($team->logo_path);
            }

            $path = $request->file('logo')->store('public/images/teams');
            $team->logo_path = Storage::url($path);
        }

        $team->fill($request->all());
        $team->save();

        //return Inertia::render('Teams/Edit', [
        //            'teams' => Team::orderBy('name')->get()
        //        ]);
        return redirect()->route('teams.edit')->with('success', "L'équipe a été modifiée avec succès");
    }



    public function destroy(Team $team)
    {
        $team->delete();
        return redirect()->route('teams')->with('message', 'Team successfully deleted.');
    }


}
