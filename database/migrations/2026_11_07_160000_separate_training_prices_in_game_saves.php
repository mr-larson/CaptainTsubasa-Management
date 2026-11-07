<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('game_saves')->whereNotNull('state')->chunkById(100, function ($saves) {
            foreach ($saves as $save) {
                $state = json_decode($save->state, true);
                $config = $state['config'] ?? [];
                if (!isset($config['training_cost']) || array_key_exists('position_training_cost', $config)) {
                    continue;
                }

                $oldCost = (int) $config['training_cost'];
                $config['position_training_cost'] = $oldCost;
                if ($oldCost === 200) {
                    $config['training_cost'] = 150;
                }

                $state['config'] = $config;
                DB::table('game_saves')->where('id', $save->id)->update([
                    'state' => json_encode($state, JSON_THROW_ON_ERROR),
                ]);
            }
        });
    }

    public function down(): void
    {
        // Les deux tarifs peuvent avoir été réglés séparément depuis la migration.
    }
};
