<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Les colonnes ENUM MySQL deviennent des chaînes : ajouter une valeur ne demande
 * plus d'ALTER TABLE brut, la liste des valeurs vit dans les enums PHP
 * (MatchStatus, BonusCardTarget, InjurySeverity) et les règles de validation.
 * Retire aussi game_saves.control_mode (toujours « both », jamais lu).
 */
return new class extends Migration
{
    /** table => [colonne => défaut ou null] */
    private const COLUMNS = [
        'bonus_cards'      => ['kind' => 'bonus', 'tier' => null, 'target' => null, 'execution_phase' => null],
        'game_bonus_cards' => ['tier' => null, 'status' => 'available'],
        'game_injuries'    => ['severity' => 'light'],
        'game_matches'     => ['status' => 'scheduled'],
        'game_sanctions'   => ['type' => null],
    ];

    public function up(): void
    {
        foreach (self::COLUMNS as $table => $columns) {
            Schema::table($table, function (Blueprint $blueprint) use ($columns) {
                foreach ($columns as $name => $default) {
                    $column = $blueprint->string($name, 32);
                    if ($default !== null) $column->default($default);
                    $column->change();
                }
            });
        }

        if (Schema::hasColumn('game_saves', 'control_mode')) {
            Schema::table('game_saves', fn (Blueprint $table) => $table->dropColumn('control_mode'));
        }
    }

    public function down(): void
    {
        Schema::table('game_saves', fn (Blueprint $table) => $table->string('control_mode')->default('both'));

        $enums = [
            'bonus_cards'      => ['kind' => ['bonus', 'malus'], 'tier' => ['bronze', 'silver', 'gold'], 'target' => ['self', 'player', 'match', 'finance', 'opponent', 'team'], 'execution_phase' => ['immediate', 'pre_match', 'post_match', 'weekly_reset']],
            'game_bonus_cards' => ['tier' => ['bronze', 'silver', 'gold'], 'status' => ['available', 'used']],
            'game_injuries'    => ['severity' => ['light', 'moderate', 'severe']],
            'game_matches'     => ['status' => ['scheduled', 'played', 'cancelled']],
            'game_sanctions'   => ['type' => ['yellow', 'red', 'double_yellow']],
        ];
        foreach ($enums as $table => $columns) {
            Schema::table($table, function (Blueprint $blueprint) use ($table, $columns) {
                foreach ($columns as $name => $values) {
                    $column = $blueprint->enum($name, $values);
                    if (($default = self::COLUMNS[$table][$name]) !== null) $column->default($default);
                    $column->change();
                }
            });
        }
    }
};
