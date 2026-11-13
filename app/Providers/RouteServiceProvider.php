<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to your application's "home" route.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    public const HOME = '/mainMenu';

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    public function boot(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        // Actions de jeu : plafond généreux par joueur (un double clic ou un
        // script emballé ne doit pas saturer la base). Lectures non limitées.
        RateLimiter::for('game-writes', function (Request $request) {
            return $request->isMethodSafe()
                ? Limit::none()
                : Limit::perMinute(120)->by($request->user()?->id ?: $request->ip());
        });

        // Création de partie : des centaines de lignes insérées par appel.
        RateLimiter::for('game-create', function (Request $request) {
            return [
                Limit::perMinute(5)->by('create-min:'.($request->user()?->id ?: $request->ip())),
                Limit::perHour(30)->by('create-hour:'.($request->user()?->id ?: $request->ip())),
            ];
        });

        $this->routes(function () {
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });
    }
}
