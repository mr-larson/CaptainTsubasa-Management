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
            // Récupérer le nom du fichier
            $fileName = time() . '.' . $request->image->getClientOriginalExtension();
            // Déplacer le fichier dans le dossier de stockage
            $request->image->storeAs('public/images/teams', $fileName);
            // Enregistrer le nom du fichier dans la base de données
            $request->merge(['image' => $fileName]);
        }

        // Créer la team
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
            // Récupérer le nom du fichier
            $fileName = time() . '.' . $request->image->getClientOriginalExtension();
            // Déplacer le fichier dans le dossier de stockage
            $request->image->storeAs('public/images/teams', $fileName);

            // Supprimer l'ancienne image si elle existe
            if($team->image) {
                Storage::delete('public/images/teams/' . $team->image);
            }

            // Mettre à jour le champ image avant de sauvegarder les autres champs
            $team->image = $fileName;
        }

        // Mettre à jour les autres champs de la team
        $team->update($request->except('image'));

        return redirect()->route('teams')->with('success', "L'équipe a été mise à jour avec succès");
    }


    public function destroy(Team $team)
    {
        $team->delete();
        return redirect()->route('teams')->with('message', 'Team successfully deleted.');
    }


}
