<?php

namespace App\Exceptions;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;

/**
 * Gestionnaire d'exceptions de l'application : ne mémorise jamais les mots de passe dans les anciennes valeurs de formulaire.
 */
class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    /**
     * Visiteur non connecté (session expirée, par exemple). Une visite Inertia en PATCH/PUT/DELETE reçoit une
     * redirection 303 : avec un 302, le navigateur rejouerait la même méthode sur /login (« PATCH method is not
     * supported for route login »).
     */
    protected function unauthenticated($request, AuthenticationException $exception)
    {
        if ($request->header('X-Inertia') && ! $request->isMethod('GET') && ! $request->expectsJson()) {
            return redirect()->guest($exception->redirectTo($request) ?? route('login'), 303);
        }

        return parent::unauthenticated($request, $exception);
    }
}
