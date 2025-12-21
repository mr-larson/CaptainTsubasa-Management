<?php

namespace App\Http\Controllers;

use App\Http\Requests\TeamRequest;
use App\Http\Resources\TeamResource;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;

class TeamController extends Controller
{
    /**
     * Liste des équipes (vue "Index").
     */
    public function index(): Response
    {
        return Inertia::render('Teams/Index', [
            'teams' => TeamResource::collection(
                Team::orderBy('name')->get()
            ),
        ]);
    }

    /**
     * Formulaire de création d’équipe.
     */
    public function create(): Response
    {
        return Inertia::render('Teams/Create');
    }

    /**
     * Enregistrement d’une nouvelle équipe.
     */
    public function store(TeamRequest $request)
    {
        $data = $request->validated();

        $team = new Team();

        // IMPORTANT : on ne "fill" pas logo/remove_logo (pas des colonnes DB)
        unset($data['remove_logo'], $data['logo']);
        $team->fill($data);

        if ($request->hasFile('logo')) {
            $team->logo_path = $this->storeTeamLogo(
                $request->file('logo'),
                $team->name
            );
        }

        $team->save();

        return redirect()->back();
    }

    /**
     * Écran d’édition (avec liste dans la sidebar).
     */
    public function edit(): Response
    {
        return Inertia::render('Teams/Edit', [
            'teams' => Team::orderBy('name')->get(),
        ]);
    }

    /**
     * Mise à jour d’une équipe.
     */
    public function update(TeamRequest $request, Team $team)
    {
        $data = $request->validated();

        // ✅ lire remove_logo AVANT unset (sinon tu le perds)
        $removeLogo = !empty($data['remove_logo']);

        // IMPORTANT : on ne "fill" pas logo/remove_logo (pas des colonnes DB)
        unset($data['remove_logo'], $data['logo']);
        $team->fill($data);

        // suppression explicite
        if ($removeLogo && $team->logo_path) {
            $this->deleteTeamLogo($team->logo_path);
            $team->logo_path = null;
        }

        // remplacement logo (prioritaire sur remove_logo si un fichier est fourni)
        if ($request->hasFile('logo')) {
            if ($team->logo_path) {
                $this->deleteTeamLogo($team->logo_path);
            }

            $team->logo_path = $this->storeTeamLogo(
                $request->file('logo'),
                $team->name
            );
        }

        $team->save();

        return redirect()->back();
    }

    /**
     * Suppression d’une équipe.
     */
    public function destroy(Team $team): RedirectResponse
    {
        if ($team->logo_path) {
            $this->deleteTeamLogo($team->logo_path);
        }

        $team->delete();

        return redirect()
            ->route('teams.edit')
            ->with('message', 'Équipe supprimée avec succès.');
    }

    // AJOUT
    private function storeTeamLogo(\Illuminate\Http\UploadedFile $file, string $teamName): string
    {
        // dossier public/images/teams
        $dir = public_path('images/teams');

        // crée le dossier si absent
        if (!File::exists($dir)) {
            File::makeDirectory($dir, 0755, true);
        }

        // filename stable + unique
        $ext = $file->getClientOriginalExtension(); // jpg/png/webp...
        $base = Str::slug($teamName); // ex: nankatsu
        $filename = $base . '-' . Str::random(6) . '.' . $ext;

        // move dans public
        $file->move($dir, $filename);

        // ce qu'on stocke en DB (chemin relatif public)
        return 'images/teams/' . $filename;
    }

    private function deleteTeamLogo(string $logoPath): void
    {
        // logoPath est du type images/teams/xxx.webp
        $absolute = public_path($logoPath);

        if (File::exists($absolute)) {
            File::delete($absolute);
        }
    }
}
