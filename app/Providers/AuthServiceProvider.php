<?php

namespace App\Providers;

use App\Models\Contract;
use App\Models\GameSaves\GameSave;
use App\Models\Player;
use App\Models\Team;
use App\Policies\ContractPolicy;
use App\Policies\GameSavePolicy;
use App\Policies\PlayerPolicy;
use App\Policies\TeamPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        GameSave::class => GameSavePolicy::class,
        Team::class     => TeamPolicy::class,
        Player::class   => PlayerPolicy::class,
        Contract::class => ContractPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        // Les administrateurs passent toutes les autorisations.
        // Retourner null laisse la main aux policies pour les non-admins.
        Gate::before(fn ($user, string $ability) => $user->isAdmin() ? true : null);
    }
}
