<?php

namespace App\Http\Controllers;

use App\Http\Requests\GameSaveRequest;
use App\Models\GameSave;
use App\Models\Team;
use App\Models\Player;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class GameSaveController extends Controller
{
    public function index(Request $request): Response
    {
        // Liste uniquement les sauvegardes du user connecté
        $gameSaves = GameSave::with('team')
            ->where('user_id', $request->user()->id)
            ->orderByDesc('updated_at')
            ->get();

        return Inertia::render('GameSaves/Index', [
            'gameSaves' => $gameSaves,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('GameSaves/Create');
    }

    /**
     * Étape 1 : on reçoit label + period
     * → on affiche l'écran de choix d'équipe (aucune sauvegarde créée pour l'instant).
     */
    public function store(Request $request): Response
    {
        $data = $request->validate([
            'label'  => ['nullable', 'string', 'max:255'],
            'period' => ['required', 'string', 'in:college'], // MVP : collège uniquement
        ]);

        // On charge les équipes avec leurs contrats + joueurs
        $teams = Team::with(['contracts.player'])
            ->orderBy('name')
            ->get();

        return Inertia::render('GameSaves/TeamSelection', [
            'label'  => $data['label'] ?? null,
            'period' => $data['period'],
            'teams'  => $teams,
        ]);
    }

    /**
     * Étape 2 : l'utilisateur a choisi une équipe et confirme.
     * Ici seulement on crée la première sauvegarde.
     */
    public function start(GameSaveRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $gameSave = GameSave::create([
            'user_id' => $request->user()->id,
            'team_id' => $data['team_id'],      // requis via le FormRequest (à ajuster si besoin)
            'period'  => $data['period'],       // 'college' pour le MVP
            'season'  => 1,
            'week'    => 1,
            'label'   => $data['label'] ?? null,
            'state'   => null,                  // tu pourras y mettre l'état initial (calendrier, fatigue, etc.)
        ]);

        return redirect()
            ->route('game-saves.play', $gameSave)
            ->with('success', 'Partie créée');
    }

    public function show(Request $request, GameSave $gameSave): Response
    {
        $this->authorizeSave($request, $gameSave);

        return Inertia::render('GameSaves/Show', [
            'gameSave' => $gameSave->load('team'),
        ]);
    }

    /**
     * Dashboard de la session (pour l'instant ton Play).
     */
    public function play(Request $request, GameSave $gameSave): Response
    {
        $this->authorizeSave($request, $gameSave);

        // Équipe contrôlée + contrats + joueurs
        $gameSave->load([
            'team.contracts.player',
        ]);

        // Toutes les équipes avec leurs contrats + joueurs (pour autres équipes + classement)
        $teams = Team::with(['contracts.player'])
            ->orderBy('name')
            ->get();

        // 👉 Joueurs libres = joueurs sans contrat dans la DB de base
        $freePlayers = Player::whereDoesntHave('contracts')
            ->orderBy('lastname')
            ->orderBy('firstname')
            ->get();

        return Inertia::render('GameSaves/Play', [
            'gameSave'    => $gameSave,
            'teams'       => $teams,
            'freePlayers' => $freePlayers,
        ]);
    }

    public function signFreeAgent(Request $request, GameSave $gameSave, Player $player): RedirectResponse
    {
        $this->authorizeSave($request, $gameSave);

        $data = $request->validate([
            'team_id' => ['required', 'integer', 'exists:teams,id'],
            'salary'  => ['nullable', 'integer', 'min:0'],
        ]);

        // On récupère l'état actuel ou un tableau vide
        $state = $gameSave->state ?? [];

        // On garantit la clé 'free_agent_signings'
        if (! isset($state['free_agent_signings'])) {
            $state['free_agent_signings'] = [];
        }

        // On évite les doublons : si le joueur est déjà signé pour cette gameSave, on ne le rajoute pas
        foreach ($state['free_agent_signings'] as $signing) {
            if ($signing['player_id'] === $player->id) {
                return back()->with('info', 'Ce joueur est déjà sous contrat dans cette partie.');
            }
        }

        $state['free_agent_signings'][] = [
            'player_id' => $player->id,
            'team_id'   => $data['team_id'],
            'salary'    => $data['salary'] ?? null,
        ];

        $gameSave->state = $state;
        $gameSave->save();

        return back()->with('success', 'Contrat proposé au joueur libre.');
    }


    /**
     * Écran de match pour une session.
     */
    public function match(Request $request, GameSave $gameSave): Response
    {
        $this->authorizeSave($request, $gameSave);

        return Inertia::render('Match/Engine', [
            'gameSaveId' => $gameSave->id,
        ]);
    }

    public function continue(Request $request): RedirectResponse
    {
        $lastSave = GameSave::where('user_id', $request->user()->id)
            ->orderByDesc('updated_at')
            ->first();

        if (! $lastSave) {
            return redirect()
                ->route('game-saves.create')
                ->with('info', 'Aucune partie existante, crée une nouvelle partie.');
        }

        // 👉 renvoie bien vers le DASHBOARD, pas le match
        return redirect()->route('game-saves.play', $lastSave);
    }


    public function update(GameSaveRequest $request, GameSave $gameSave)
    {
        $this->authorizeSave($request, $gameSave);

        $data = $request->validated();

        $gameSave->fill($data);
        $gameSave->save();

        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', 'Sauvegarde mise à jour.');
    }

    public function destroy(Request $request, GameSave $gameSave): RedirectResponse
    {
        $this->authorizeSave($request, $gameSave);

        $gameSave->delete();

        return redirect()
            ->route('game-saves.index')
            ->with('success', 'Sauvegarde supprimée.');
    }

    private function authorizeSave(Request $request, GameSave $gameSave): void
    {
        if ($gameSave->user_id !== $request->user()->id) {
            abort(403);
        }
    }
}
