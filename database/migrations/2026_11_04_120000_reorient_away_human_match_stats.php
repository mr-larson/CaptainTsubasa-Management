<?php

use App\Models\GameSaves\GameMatch;
use App\Services\Matches\MatchStatsOrientation;
use Illuminate\Database\Migrations\Migration;

/**
 * Remet dans le repère domicile / extérieur les match_stats des matchs joués
 * par un humain à l'extérieur : le moteur y enregistrait l'équipe humaine
 * comme « home » (stats d'équipe, titulaires et camp des événements inversés
 * dans le calendrier et le replay). Idempotent : un match réparé porte le
 * marqueur `internal_is_home` et n'est plus détecté comme inversé.
 */
return new class extends Migration
{
    public function up(): void
    {
        $service = app(MatchStatsOrientation::class);

        // Sans filtre de saison : la colonne `season` n'existe pas encore à ce stade.
        GameMatch::withoutGlobalScopes()
            ->where('status', 'played')
            ->whereNotNull('match_stats')
            ->orderBy('id')
            ->chunkById(100, function ($matches) use ($service) {
                foreach ($matches as $match) {
                    $service->repair($match);
                }
            });
    }

    public function down(): void
    {
        // Pas de retour arrière : l'ancien repère était faux.
    }
};
