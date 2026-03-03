<?php

namespace App\Http\Controllers\GameSaves;

use App\Http\Controllers\Controller;
use App\Http\Requests\GamePlayerRequest;
use App\Models\GameSaves\GamePlayer;
use App\Models\GameSaves\GameSave;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class GamePlayerController extends Controller
{
    /**
     * Liste des joueurs d'une sauvegarde.
     */
    public function index(Request $request, GameSave $gameSave): Response
    {
        // Sécurité ownership
        if ($gameSave->user_id !== $request->user()->id) {
            abort(403);
        }

        $players = GamePlayer::with(['basePlayer', 'contracts'])
            ->where('game_save_id', $gameSave->id)
            ->orderBy('lastname')
            ->orderBy('firstname')
            ->get();

        return Inertia::render('GameSaves/GamePlayers/Index', [
            'gameSave' => $gameSave,
            'players'  => $players,
        ]);
    }

    /**
     * Formulaire de création de joueur dans la sauvegarde.
     */
    public function create(Request $request, GameSave $gameSave): Response
    {
        if ($gameSave->user_id !== $request->user()->id) {
            abort(403);
        }

        return Inertia::render('GameSaves/GamePlayers/Create', [
            'gameSave' => $gameSave,
        ]);
    }

    /**
     * Création d'un joueur dans une sauvegarde.
     */
    public function store(GamePlayerRequest $request, GameSave $gameSave)
    {
        if ($gameSave->user_id !== $request->user()->id) {
            abort(403);
        }

        $data = $request->validated();

        $player = new GamePlayer();
        $player->game_save_id = $gameSave->id;

        // fill sans les champs techniques
        $player->fill(
            $request->safe()->except(['photo', 'remove_photo'])
        );

        // Upload éventuel de la photo
        if ($request->hasFile('photo')) {
            $player->photo_path = $this->storeGamePlayerPhoto(
                $request->file('photo'),
                $player->lastname ?: $player->firstname ?: 'player',
                $gameSave->id
            );
        }

        $player->save();

        return redirect()->route('game-saves.players.index', [
            'gameSave' => $gameSave->id,
        ])->with('success', 'Joueur créé.');
    }

    /**
     * Formulaire d'édition.
     */
    public function edit(Request $request, GameSave $gameSave, GamePlayer $player): Response
    {
        if ($gameSave->user_id !== $request->user()->id) {
            abort(403);
        }

        if ($player->game_save_id !== $gameSave->id) {
            abort(403);
        }

        return Inertia::render('GameSaves/GamePlayers/Edit', [
            'gameSave' => $gameSave,
            'player'   => $player,
        ]);
    }

    /**
     * Mise à jour d'un joueur de partie.
     */
    public function update(GamePlayerRequest $request, GameSave $gameSave, GamePlayer $player)
    {
        if ($gameSave->user_id !== $request->user()->id) {
            abort(403);
        }

        if ($player->game_save_id !== $gameSave->id) {
            abort(403);
        }

        $data = $request->validated();

        // Gestion suppression photo
        if ($request->boolean('remove_photo') && $player->photo_path) {
            $this->deleteGamePlayerPhoto($player->photo_path);
            $player->photo_path = null;
        }

        // Upload nouvelle photo
        if ($request->hasFile('photo')) {
            if ($player->photo_path) {
                $this->deleteGamePlayerPhoto($player->photo_path);
            }

            $player->photo_path = $this->storeGamePlayerPhoto(
                $request->file('photo'),
                $data['lastname'] ?? ($data['firstname'] ?? 'player'),
                $gameSave->id
            );
        }

        // Données textuelles / stats
        $player->fill(
            $request->safe()->except(['photo', 'remove_photo'])
        );

        $player->save();

        return back()->with('success', 'Joueur mis à jour.');
    }

    /**
     * Suppression d'un joueur de partie.
     */
    public function destroy(Request $request, GameSave $gameSave, GamePlayer $player)
    {
        if ($gameSave->user_id !== $request->user()->id) {
            abort(403);
        }

        if ($player->game_save_id !== $gameSave->id) {
            abort(403);
        }

        // Règle métier : interdiction de supprimer un joueur sous contrat
        if ($player->contracts()->exists()) {
            abort(403, 'Impossible de supprimer un joueur qui possède un contrat dans cette partie.');
        }

        if ($player->photo_path) {
            $this->deleteGamePlayerPhoto($player->photo_path);
        }

        $player->delete();

        return redirect()->route('game-saves.players.index', [
            'gameSave' => $gameSave->id,
        ])->with('success', 'Joueur supprimé.');
    }

    /**
     * Stockage de la photo du joueur sur le disque public.
     */
    private function storeGamePlayerPhoto(UploadedFile $file, string $name, int $gameSaveId): string
    {
        $dir = "images/game-players/{$gameSaveId}";

        $filename = Str::slug($name).'-'.Str::random(6).'.'.$file->getClientOriginalExtension();

        // On stocke sur le disque "public" pour être compatible avec Storage::url()
        Storage::disk('public')->putFileAs($dir, $file, $filename);

        return "{$dir}/{$filename}";
    }

    /**
     * Suppression de la photo du joueur depuis le disque public.
     */
    private function deleteGamePlayerPhoto(string $path): void
    {
        Storage::disk('public')->delete($path);
    }
}
