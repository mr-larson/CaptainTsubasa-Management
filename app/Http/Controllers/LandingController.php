<?php

namespace App\Http\Controllers;

use App\Models\Team;
use App\Models\Player;
use Illuminate\View\View;

class LandingController extends Controller
{
    /**
     * Page d'accueil publique (avant connexion).
     */
    public function index(): View
    {
        // Toutes les équipes (tu peux adapter l'ordre)
        $teams = Team::orderBy('name')
            ->get(['id', 'name']); // ajoute d'autres colonnes si tu en as (ville, logo, etc.)

        // Quelques joueurs "vitrine" (les plus chers, donc en général les meilleurs)
        $players = Player::orderByDesc('cost')
            ->limit(12)
            ->get(['id', 'firstname', 'lastname', 'position', 'cost', 'photo_path']);

        return view('landing', [
            'teams'   => $teams,
            'players' => $players,
        ]);
    }
}

