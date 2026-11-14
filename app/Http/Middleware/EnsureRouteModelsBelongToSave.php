<?php

namespace App\Http\Middleware;

use App\Models\GameSaves\GameSave;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Garantit que toute ressource liée par la route (joueur, équipe, contrat,
 * match, carte…) appartient bien à la sauvegarde {gameSave} de l'URL.
 *
 * Le contrôle ne dépend plus de la mémoire de chaque contrôleur (passer les
 * enfants à authorizeGameSave) : un nouveau paramètre de route est vérifié
 * automatiquement. Refus par défaut : un modèle sans game_save_id (donc sans
 * lien prouvé avec la sauvegarde) est refusé lui aussi. 404 plutôt que 403, comme
 * le ferait scopeBindings(), pour ne pas révéler l'existence de la ressource.
 */
class EnsureRouteModelsBelongToSave
{
    /** Refuse (404) toute ressource de la route dont le game_save_id est absent ou diffère de la sauvegarde de l'URL. */
    public function handle(Request $request, Closure $next): Response
    {
        $parameters = $request->route()?->parameters() ?? [];
        $gameSave   = $parameters['gameSave'] ?? null;

        if ($gameSave instanceof GameSave) {
            foreach ($parameters as $name => $value) {
                if ($value === $gameSave || ! $value instanceof Model) continue;

                $owner = $value->getAttribute('game_save_id');
                abort_if(
                    (int) $owner !== (int) $gameSave->getKey(),
                    404,
                    "La ressource « {$name} » n'appartient pas à cette sauvegarde."
                );
            }
        }

        return $next($request);
    }
}
