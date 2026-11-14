<?php

namespace App\Http\Middleware;

use App\Models\GameSaves\GameSave;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

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
    /**
     * Pour une écriture autorisée sur une sauvegarde : ouvre une transaction, verrouille la ligne de la sauvegarde et la recharge avant de poursuivre. La transaction est validée si la requête aboutit, annulée si elle se termine par une exception. Les lectures et requêtes non autorisées passent sans verrou.
     */
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

        // Transaction gérée à la main : Laravel convertit en réponse toute
        // exception levée par le contrôleur AVANT qu'elle ne remonte ici.
        // DB::transaction() ne la verrait donc jamais et validerait les
        // écritures partielles. On annule dès que la réponse porte une exception.
        DB::beginTransaction();

        try {
            GameSave::whereKey($gameSave->getKey())->lockForUpdate()->first();
            $gameSave->refresh();

            $response = $next($request);
        } catch (Throwable $e) {
            DB::rollBack();

            throw $e;
        }

        if ($this->failed($response)) {
            DB::rollBack();
        } else {
            DB::commit();
        }

        return $response;
    }

    /**
     * Vrai si la réponse est née d'une exception (abort, erreur de validation,
     * erreur serveur…) : rien de ce que la requête a écrit ne doit alors être gardé.
     */
    private function failed(Response $response): bool
    {
        return ($response->exception ?? null) !== null;
    }
}
