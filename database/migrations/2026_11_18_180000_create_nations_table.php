<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Catalogue des nations du mode Coupe du Monde (jumeau de `teams` pour les clubs).
 *
 * `name` reprend EXACTEMENT les valeurs de App\Enums\Nationality, qui sont aussi celles de
 * `players.nationality` : aucune donnée joueur à migrer. La table est alimentée ici (et non
 * dans un seeder) car la production n'exécute pas les seeders au déploiement.
 * `game_teams.base_nation_id` relie une sélection de partie à son entrée du catalogue.
 */
return new class extends Migration
{
    /** Nom (valeur de Nationality) => code ISO 3166 (alpha-2, ou ISO 3166-2 pour l'Angleterre et le pays de Galles). */
    private const NATIONS = [
        'Japon' => 'JP', 'Allemagne' => 'DE', 'France' => 'FR', 'Brésil' => 'BR', 'Argentine' => 'AR',
        'Italie' => 'IT', 'Pays-Bas' => 'NL', 'Uruguay' => 'UY', 'Angleterre' => 'GB-ENG', 'Suède' => 'SE',
        'Espagne' => 'ES', 'Mexique' => 'MX', 'États-Unis' => 'US', 'Arabie saoudite' => 'SA',
        'Corée du Sud' => 'KR', 'Chine' => 'CN', 'Ouzbékistan' => 'UZ', 'Nigéria' => 'NG', 'Australie' => 'AU',
        'Ghana' => 'GH', 'Thaïlande' => 'TH', 'Portugal' => 'PT', 'Croatie' => 'HR', 'Belgique' => 'BE',
        'Trinité-et-Tobago' => 'TT', 'Danemark' => 'DK', 'Paraguay' => 'PY', 'République tchèque' => 'CZ',
        'Irlande' => 'IE', 'Roumanie' => 'RO', 'Ukraine' => 'UA', 'Bulgarie' => 'BG', 'Liberia' => 'LR',
        'Hongrie' => 'HU', 'Pologne' => 'PL', 'Maroc' => 'MA', 'Slovénie' => 'SI', 'Costa Rica' => 'CR',
        "Côte d'Ivoire" => 'CI', 'Serbie' => 'RS', 'RD Congo' => 'CD', 'Canada' => 'CA', 'Norvège' => 'NO',
        'Pérou' => 'PE', 'Albanie' => 'AL', 'Guinée' => 'GN', 'Finlande' => 'FI', 'Pays de Galles' => 'GB-WLS',
        'Yougoslavie' => 'YU',
    ];

    public function up(): void
    {
        Schema::create('nations', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('code', 8);
            $table->string('flag_path')->nullable();
            $table->string('tactical_style')->default('balanced');
            $table->string('management_philosophy')->default('collective');
            $table->string('default_formation')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        $now = now();
        DB::table('nations')->insert(array_map(
            fn (string $name, string $code) => ['name' => $name, 'code' => $code, 'created_at' => $now, 'updated_at' => $now],
            array_keys(self::NATIONS),
            self::NATIONS,
        ));

        Schema::table('game_teams', function (Blueprint $table) {
            $table->foreignId('base_nation_id')->nullable()->after('base_team_id')
                ->constrained('nations')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('game_teams', function (Blueprint $table) {
            $table->dropConstrainedForeignId('base_nation_id');
        });
        Schema::dropIfExists('nations');
    }
};
