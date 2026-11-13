<?php

namespace Database\Seeders;

use App\Models\Player;
use App\Models\Team;
use App\Models\Traits\CalculatesWeeklyCost;
use Database\Seeders\Concerns\GuardsReferenceReseed;
use Database\Seeders\Players\ClubRosters;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Contrats de référence (table `contracts`) : place les joueurs du catalogue dans
 * leur club d'origine d'après ClubRosters (effectif et capitaine de chaque club),
 * avec un salaire calculé d'après leurs stats et des dates de début et de fin
 * aléatoires. Doit passer APRÈS TeamSeeder et PlayerSeeder.
 */
class ContractSeeder extends Seeder
{
    use GuardsReferenceReseed;
    use CalculatesWeeklyCost;

    /**
     * Vide la table `contracts` puis crée un contrat par joueur de chaque effectif de
     * ClubRosters. Signale (sans s'arrêter) une équipe ou un joueur introuvable dans le
     * catalogue, et marque le capitaine de chaque club.
     */
    public function run(): void
    {
        if ($this->refusesProductionReseed('contracts')) {
            return;
        }

        Schema::disableForeignKeyConstraints();
        DB::table('contracts')->truncate();
        Schema::enableForeignKeyConstraints();

        $teamsByName   = Team::pluck('id', 'name');
        $playersByFullName = Player::all()->keyBy(fn(Player $p) => "{$p->firstname} {$p->lastname}");

        $teamsPlayers = ClubRosters::players();
        $captains     = ClubRosters::captains();

        $contracts = [];

        foreach ($teamsPlayers as $teamName => $playerNames) {
            $teamId = $teamsByName[$teamName] ?? null;
            if (!$teamId) {
                dump("Team not found: {$teamName}");
                continue;
            }

            foreach ($playerNames as $fullName) {
                $player = $playersByFullName->get($fullName);
                if (!$player) {
                    dump("Player not found: {$fullName}");
                    continue;
                }

                $contracts[] = [
                    'player_id'  => $player->id,
                    'team_id'    => $teamId,
                    'salary'     => $this->calculateWeeklyCost($player->stats ?? [], $player->position?->value),
                    'start_date' => now()->subMonths(rand(1, 12))->subDays(rand(1, 30)),
                    'end_date'   => now()->addMonths(rand(1, 12))->addDays(rand(1, 30)),
                    'is_captain' => isset($captains[$teamName]) && $captains[$teamName] === $fullName ? 1 : 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        if (!empty($contracts)) {
            DB::table('contracts')->insert($contracts);
        }
    }
}
