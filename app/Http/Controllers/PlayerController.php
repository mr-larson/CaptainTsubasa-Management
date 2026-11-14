<?php

namespace App\Http\Controllers;

use App\Enums\PlayerPosition;
use App\Http\Requests\PlayerRequest;
use App\Models\Player;
use App\Models\Team;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;


/** Gestion des joueurs de la base de données (hors sauvegardes) : liste, fiche, création, modification, suppression et photo. */
class PlayerController extends Controller
{
    /**
     * Écran unique des joueurs de la base : liste + fiche à onglets (fiche,
     * coups spéciaux, contrat), création comprise (fiche vide sur la page).
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Player::class);

        return Inertia::render('Players/Index', [
            'players'        => Player::with('contracts.team')->orderBy('firstname')->get(),
            'teams'          => Team::orderBy('name')->get(['id', 'name', 'logo_path']),
            'positions'      => PlayerPosition::values(),
            'positionLabels' => PlayerPosition::labels(),
            // Joueur à ouvrir (ex. juste après sa création) / fiche vide demandée.
            'selectedId'     => $request->integer('selected') ?: null,
            'startCreate'    => $request->boolean('create'),
        ]);
    }

    /** Ancienne URL de création : ouvre l'écran en mode création. */
    public function create(): RedirectResponse
    {
        return redirect()->route('players.index', ['create' => 1]);
    }

    /** Crée un joueur (photo enregistrée sur le disque public si fournie) puis ouvre l'écran des joueurs sur ce nouveau joueur. */
    public function store(PlayerRequest $request): RedirectResponse
    {
        $data = $request->validated();

        if ($request->hasFile('photo')) {
            $data['photo_path'] = $request->file('photo')->store('players', 'public');
        }

        $player = Player::create($data);

        return redirect()
            ->route('players.index', ['selected' => $player->id])
            ->with('success', 'Le joueur a été créé avec succès.');
    }

    /** Ancienne URL d'édition : même écran que la liste. */
    public function edit(Request $request): Response
    {
        return $this->index($request);
    }

    /** Met à jour un joueur : les listes de postes secondaires et de coups spéciaux absentes de la requête sont vidées, et une nouvelle photo s'ajoute sans supprimer l'ancienne. Revient en arrière avec un message. */
    public function update(PlayerRequest $request, Player $player): RedirectResponse
    {
        $data = $request->validated();

        // En multipart, un tableau vide n'est pas envoyé : postes secondaires
        // absents = liste vidée (le formulaire envoie toujours ce champ).
        $data['secondary_positions'] = $data['secondary_positions'] ?? [];
        // Idem pour les coups spéciaux (l'écran envoie toujours la liste complète).
        $data['special_moves'] = $data['special_moves'] ?? [];

        // Nouvelle photo : l'ancienne est conservée, car les saves en copient le
        // chemin (game_players.photo_path) ; l'effacer casserait leur affichage.
        if ($request->hasFile('photo')) {
            $data['photo_path'] = $request->file('photo')->store('players', 'public');
        }

        $player->update($data);

        return back()->with('success', 'Le joueur a été mis à jour avec succès.');
    }

    /** Supprime le joueur (suppression douce, photo conservée) et revient à la liste des joueurs. */
    public function destroy(Player $player): RedirectResponse
    {
        $this->authorize('delete', $player);

        // Photo conservée (suppression douce, et les saves en copient le chemin).
        $player->delete();

        return redirect()
            ->route('players.index')
            ->with('success', 'Le joueur a été supprimé avec succès.');
    }
}
