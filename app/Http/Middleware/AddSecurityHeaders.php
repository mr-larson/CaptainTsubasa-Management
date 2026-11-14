<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ajoute à toutes les réponses les en-têtes de sécurité de base du navigateur.
 *
 * - X-Frame-Options : le site ne peut pas être affiché dans une iframe d'un
 *   autre domaine (protection contre le détournement de clic).
 * - X-Content-Type-Options : le navigateur ne devine pas le type d'un fichier
 *   (une image envoyée par un joueur n'est jamais exécutée comme du script).
 * - Referrer-Policy : seule l'origine (sans le chemin) est transmise aux sites externes.
 * - Strict-Transport-Security : en HTTPS, le navigateur n'utilisera plus que HTTPS.
 *
 * Pas de Content-Security-Policy ici : elle demande des essais avec Vite et
 * Inertia, à traiter à part.
 */
class AddSecurityHeaders
{
    /** Durée (en secondes) pendant laquelle le navigateur impose HTTPS : un an. */
    private const HSTS_MAX_AGE = 31536000;

    /** Laisse passer la requête puis complète la réponse, sans écraser un en-tête déjà posé. */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $headers = [
            'X-Frame-Options'        => 'SAMEORIGIN',
            'X-Content-Type-Options' => 'nosniff',
            'Referrer-Policy'        => 'strict-origin-when-cross-origin',
        ];

        // HSTS n'a de sens qu'en HTTPS : en local (HTTP) il n'est pas envoyé.
        if ($request->isSecure()) {
            $headers['Strict-Transport-Security'] = 'max-age='.self::HSTS_MAX_AGE;
        }

        foreach ($headers as $name => $value) {
            if (! $response->headers->has($name)) {
                $response->headers->set($name, $value);
            }
        }

        return $response;
    }
}
