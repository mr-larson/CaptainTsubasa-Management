<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

/** Fournisseur de services principal : branche la source d'aléatoire utilisée par les services de jeu. */
class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Source d'aléatoire injectable ; les tests de rejeu la remplacent par SeededRandomizer.
        $this->app->bind(\App\Contracts\Randomizer::class, \App\Support\Random\NativeRandomizer::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
