<?php

namespace App\Http\Middleware;

use App\Models\GameSaves\GameSave;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sérialise toute écriture (POST/PUT/PATCH/DELETE) sous /game-saves/{gameSave} :
 * une seule requête à la fois par sauvegarde, dans une transaction.
 *
 * Évite les « vérifie puis crée » concurrents (double promesse) et les
 * écritures en deux temps non atomiques (changement de capitaine). Les
 * contrôleurs qui appellent déjà withSaveLock() imbriquent simplement une
 * transaction de plus : le verrou est déjà détenu.
 *
 * Le modèle lié à la route est rafraîchi sous verrou, pour que le contrôleur
 * travaille sur l'état à jour et non sur celui lu avant l'attente.
 */
class LockGameSave
{
    public function handle(Request $request, Closure $next): Response
    {
        $gameSave = $request->route('gameSave');

        if ($request->isMethodSafe() || ! $gameSave instanceof GameSave) {
            return $next($request);
        }

        // Une requête non autorisée est refusée par le contrôleur : on ne lui
        // laisse pas prendre le verrou.
        if (! $request->user()?->can('update', $gameSave)) {
            return $next($request);
        }

        return DB::transaction(function () use ($request, $next, $gameSave) {
            GameSave::whereKey($gameSave->getKey())->lockForUpdate()->first();
            $gameSave->refresh();

            return $next($request);
        });
    }
}
