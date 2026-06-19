<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    /**
     * Réserve la route aux administrateurs.
     *
     * Gate de rôle (par opposition aux policies, qui gèrent l'autorisation
     * ligne par ligne sur l'ownership des sauvegardes).
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->isAdmin()) {
            abort(403, 'Accès réservé aux administrateurs.');
        }

        return $next($request);
    }
}
