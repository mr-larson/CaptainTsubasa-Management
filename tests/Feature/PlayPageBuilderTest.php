<?php

namespace Tests\Feature;

use App\Services\PlayPageBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsGameWorld;
use Tests\TestCase;

/**
 * Données de la page de jeu : le contrat de clés attendu par Play.vue, le
 * hot-seat, et le fait que la construction ne modifie rien en base.
 */
class PlayPageBuilderTest extends TestCase
{
    use RefreshDatabase;
    use BuildsGameWorld;

    private function builder(): PlayPageBuilder
    {
        return app(PlayPageBuilder::class);
    }

    private function world(int $humans = 1): array
    {
        $save = $this->makeSave();
        $mine = null;
        foreach (range(1, $humans) as $seat) {
            [$team] = $this->makeTeamWithSquad($save, ['is_controlled' => true, 'human_seat' => $seat]);
            $mine ??= $team;
        }
        foreach (range(1, 2) as $_) {
            $this->makeTeamWithSquad($save);
        }
        $save->update(['controlled_game_team_id' => $mine->id]);

        return [$save->fresh(), $mine];
    }

    public function test_props_keep_the_keys_the_page_reads(): void
    {
        [$save, $mine] = $this->world();

        $props = $this->builder()->build($save);

        foreach ([
            'gameSave', 'teams', 'matches', 'freePlayers', 'controlledTeam', 'hotSeat', 'managementStats',
            'playerSeasonStats', 'activeInjuries', 'activeSuspensions', 'activePositionTrainings', 'activeYellowCards',
            'moraleLogs', 'playerPromises', 'playerDeclarations', 'bonusCardOffers', 'bonusCardInventory',
            'sponsorChallenges', 'sponsorResults', 'incomingMalus', 'activeShields', 'gameConfig',
            'aiTrainingInitial', 'tournament', 'career', 'seasonChallenges', 'incomeModel',
            'duoLinks', 'playerChemistry', 'learnedDuos', 'midseasonBreakRecovery', 'contractSeasonEndWeek',
        ] as $key) {
            $this->assertArrayHasKey($key, $props, "Clé « {$key} » manquante pour Play.vue");
        }
        $this->assertSame($mine->id, $props['controlledTeam']->id);
        $this->assertCount(3, $props['teams']); // l'équipe du manager + 2 équipes IA
    }

    public function test_hot_seat_is_null_for_one_manager_and_ordered_for_several(): void
    {
        [$solo] = $this->world(1);
        $this->assertNull($this->builder()->build($solo)['hotSeat']);

        [$duo] = $this->world(2);
        $hotSeat = $this->builder()->build($duo)['hotSeat'];

        $this->assertSame(2, $hotSeat['total']);
        $this->assertSame([1, 2], array_column($hotSeat['players'], 'seat'));
        $this->assertTrue($hotSeat['players'][0]['is_active']);
    }

    /** Compte les requêtes d'écriture (INSERT / UPDATE / DELETE) émises pendant $action. */
    private function countWrites(callable $action): int
    {
        $writes = 0;
        \Illuminate\Support\Facades\DB::listen(function ($query) use (&$writes) {
            if (preg_match('/^\s*(insert|update|delete)\b/i', $query->sql)) {
                $writes++;
            }
        });
        $action();

        return $writes;
    }

    public function test_building_the_page_writes_nothing_once_the_shop_is_fresh(): void
    {
        [$save] = $this->world();

        // Comme le contrôleur : la boutique est rafraîchie (sous verrou) AVANT la construction.
        $this->assertTrue($this->builder()->shopNeedsRefresh($save));
        $this->builder()->refreshShop($save);
        $save = $save->fresh();
        $this->assertFalse($this->builder()->shopNeedsRefresh($save));

        $this->assertSame(0, $this->countWrites(fn () => $this->builder()->build($save)));
    }

    public function test_a_stale_shop_is_shown_empty_instead_of_being_regenerated_by_the_builder(): void
    {
        [$save] = $this->world(); // boutique jamais tirée : périmée

        $props = null;
        $writes = $this->countWrites(function () use ($save, &$props) {
            $props = $this->builder()->build($save);
        });

        $this->assertSame(0, $writes);
        $this->assertSame([], $props['bonusCardOffers']);
    }

    public function test_shop_refresh_is_only_needed_with_a_controlled_team(): void
    {
        $orphan = $this->makeSave(); // pas d'équipe contrôlée

        $this->assertFalse($this->builder()->shopNeedsRefresh($orphan));
    }
}
