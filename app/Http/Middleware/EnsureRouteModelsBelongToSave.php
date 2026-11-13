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
 * automatiquement dès qu'il expose un game_save_id. 404 plutôt que 403, comme
 * le ferait scopeBindings(), pour ne pas révéler l'existence de la ressource.
 */
class EnsureRouteModelsBelongToSave
{
    public function handle(Request $request, Closure $next): Response
    {
        $parameters = $request->route()?->parameters() ?? [];
        $gameSave   = $parameters['gameSave'] ?? null;

        if ($gameSave instanceof GameSave) {
            foreach ($parameters as $name => $value) {
                if ($value === $gameSave || ! $value instanceof Model) continue;

                $owner = $value->getAttribute('game_save_id');
                abort_if(
                    $owner !== null && (int) $owner !== (int) $gameSave->getKey(),
                    404,
                    "La ressource « {$name} » n'appartient pas à cette sauvegarde."
                );
            }
        }

        return $next($request);
    }
}
