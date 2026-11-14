<?php

namespace App\Providers;

use App\Models\Contract;
use App\Models\GameSaves\GameSave;
use App\Models\PeriodPackage;
use App\Models\Player;
use App\Models\Team;
use App\Policies\ContractPolicy;
use App\Policies\GameSavePolicy;
use App\Policies\PeriodPackagePolicy;
use App\Policies\PlayerPolicy;
use App\Policies\TeamPolicy;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

/** Enregistre les policies de chaque modèle, donne aux administrateurs tous les droits et fixe l'adresse des liens de réinitialisation de mot de passe. */
class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        GameSave::class     => GameSavePolicy::class,
        Team::class         => TeamPolicy::class,
        Player::class       => PlayerPolicy::class,
        Contract::class     => ContractPolicy::class,
        PeriodPackage::class => PeriodPackagePolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        // Les administrateurs passent toutes les autorisations.
        // Retourner null laisse la main aux policies pour les non-admins.
        Gate::before(fn ($user, string $ability) => $user->isAdmin() ? true : null);

        // Lien de réinitialisation construit depuis APP_URL, jamais depuis l'en-tête
        // Host de la requête : sinon une requête forgée ferait partir par e-mail un
        // lien pointant vers un autre domaine (TrustHosts n'est pas activé).
        ResetPassword::createUrlUsing(fn ($user, string $token) => rtrim((string) config('app.url'), '/')
            .route('password.reset', ['token' => $token, 'email' => $user->getEmailForPasswordReset()], false));
    }
}
