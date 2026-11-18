<?php

namespace Tests\Feature;

use App\Http\Middleware\HandleInertiaRequests;
use App\Models\GameSaves\GameContract;
use App\Models\GameSaves\GameSave;
use App\Models\GameSaves\GameTeam;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\BuildsGameWorld;
use Tests\TestCase;

/**
 * Rechargement partiel de la page de jeu : une action de composition ne redemande que les props
 * qu'elle modifie (`only`), et le serveur ne calcule alors pas les autres (agents libres,
 * calendrier, cartes…). Le message flash, lui, arrive toujours.
 */
class PlayPartialReloadTest extends TestCase
{
    use RefreshDatabase;
    use BuildsGameWorld;

    private function world(): array
    {
        $save = $this->makeSave();
        [$mine] = $this->makeTeamWithSquad($save, ['is_controlled' => true, 'human_seat' => 1]);
        $this->makeTeamWithSquad($save);
        $this->makeTeamWithSquad($save);
        $save->update(['controlled_game_team_id' => $mine->id]);
        $save = $save->fresh();
        $this->builderRefreshShop($save);

        return [$save->fresh(), $mine, User::find($save->user_id)];
    }

    /** La boutique est tirée avant l'affichage, comme le fait le contrôleur sous verrou. */
    private function builderRefreshShop(GameSave $save): void
    {
        app(\App\Services\Game\PlayPageBuilder::class)->refreshShop($save);
    }

    /** En-têtes d'une visite Inertia ; `$only` non vide = visite partielle de la page de jeu. */
    private function inertia(array $only = []): array
    {
        $headers = ['X-Inertia' => 'true'];
        $version = app(HandleInertiaRequests::class)->version(request());
        if ($version) {
            $headers['X-Inertia-Version'] = $version;
        }
        if ($only) {
            $headers['X-Inertia-Partial-Component'] = 'GameSaves/Play';
            $headers['X-Inertia-Partial-Data'] = implode(',', $only);
        }

        return $headers;
    }

    /** Tables lues pendant $action. */
    private function queriedSql(callable $action): array
    {
        $queries = [];
        DB::listen(function ($query) use (&$queries) {
            $queries[] = $query->sql;
        });
        $action();

        return $queries;
    }

    public function test_a_full_visit_returns_every_prop(): void
    {
        [$save, , $user] = $this->world();

        $props = $this->actingAs($user)
            ->withHeaders($this->inertia())
            ->get(route('game-saves.Play', $save))
            ->assertOk()
            ->json('props');

        foreach (['gameSave', 'teams', 'matches', 'freePlayers', 'controlledTeam', 'bonusCardInventory', 'playerSeasonStats', 'career', 'incomeModel'] as $key) {
            $this->assertArrayHasKey($key, $props, "Prop « {$key} » absente d'une visite complète");
        }
        $this->assertCount(3, $props['teams']);
        $this->assertSame($save->controlled_game_team_id, $props['controlledTeam']['id']);
    }

    public function test_a_partial_visit_returns_only_the_requested_props_and_the_always_ones(): void
    {
        [$save, , $user] = $this->world();

        $props = $this->actingAs($user)
            ->withHeaders($this->inertia(['gameSave', 'controlledTeam', 'teams']))
            ->get(route('game-saves.Play', $save))
            ->assertOk()
            ->json('props');

        $this->assertEqualsCanonicalizing(['gameSave', 'controlledTeam', 'teams', 'errors', 'flash'], array_keys($props));
        $this->assertCount(3, $props['teams']);
    }

    public function test_a_partial_visit_does_not_run_the_queries_of_the_props_it_skips(): void
    {
        [$save, , $user] = $this->world();

        $full = $this->queriedSql(fn () => $this->actingAs($user)
            ->withHeaders($this->inertia())->get(route('game-saves.Play', $save))->assertOk());
        $partial = $this->queriedSql(fn () => $this->actingAs($user)
            ->withHeaders($this->inertia(['gameSave', 'controlledTeam', 'teams']))->get(route('game-saves.Play', $save))->assertOk());

        $reads = fn (array $queries, string $needle) => count(array_filter($queries, fn ($sql) => str_contains($sql, $needle)));

        // Le calendrier (sélection avec `has_stats`) et l'inventaire de cartes sont lus par une visite
        // complète, pas par la partielle. (Le simple `exists` de préparation de saison, fait par le
        // contrôleur avant la construction, reste dans les deux cas.)
        $this->assertGreaterThan(0, $reads($full, 'has_stats'));
        $this->assertSame(0, $reads($partial, 'has_stats'));
        $this->assertGreaterThan(0, $reads($full, 'from `game_bonus_cards`'));
        $this->assertSame(0, $reads($partial, 'from `game_bonus_cards`'));
        $this->assertLessThan(count($full), count($partial));
    }

    public function test_the_controlled_team_keeps_its_renewal_cost_in_the_teams_prop(): void
    {
        [$save, $mine, $user] = $this->world();

        // Seule `teams` est demandée : le coût de prolongation de l'équipe contrôlée doit y figurer comme avant.
        $props = $this->actingAs($user)
            ->withHeaders($this->inertia(['teams']))
            ->get(route('game-saves.Play', $save))
            ->assertOk()
            ->json('props');

        $team = collect($props['teams'])->firstWhere('id', $mine->id);
        $this->assertArrayHasKey('renewal_cost', $team['contracts'][0]['game_player']);
        $other = collect($props['teams'])->firstWhere('id', '!=', $mine->id);
        $this->assertArrayNotHasKey('renewal_cost', $other['contracts'][0]['game_player']);
    }

    public function test_the_flash_message_of_an_action_survives_a_partial_reload(): void
    {
        [$save, $mine, $user] = $this->world();
        $contract = GameContract::where('game_team_id', $mine->id)->first();

        $props = $this->actingAs($user)
            ->followingRedirects()
            ->withHeaders($this->inertia(['gameSave', 'controlledTeam', 'teams']))
            ->patch(route('game-saves.contracts.toggle-captain', ['gameSave' => $save, 'contract' => $contract]))
            ->assertOk()
            ->json('props');

        $this->assertSame('Nouveau capitaine désigné.', $props['flash']['success'] ?? null);
        $this->assertArrayNotHasKey('matches', $props);
        $this->assertTrue((bool) $contract->fresh()->is_captain);
    }

    public function test_buying_a_card_only_needs_the_budget_the_inventory_and_the_shop(): void
    {
        [$save, $mine, $user] = $this->world();
        $mine->update(['budget' => 1000]);
        $card = $this->makeBonusCard(['tier' => 'gold', 'cost' => 200]);
        $save->update(['state' => ['shop' => [
            'season' => $save->season, 'week' => $save->week,
            'offers_by_team' => [$mine->id => [[
                'bonus_card_id' => $card->id, 'name' => $card->name, 'tier' => 'gold', 'cost' => 200,
            ]]],
        ]]]);

        $this->actingAs($user)->post(route('game-saves.bonus-cards.buy', $save), [
            'bonus_card_id' => $card->id, 'tier' => 'gold',
        ])->assertSessionHasNoErrors();

        // Rechargement qui suit l'achat : seules les props de CARD_BUY_PROPS sont demandées.
        $props = $this->actingAs($user)
            ->withHeaders($this->inertia(['controlledTeam', 'teams', 'bonusCardInventory', 'bonusCardOffers']))
            ->get(route('game-saves.Play', $save))
            ->assertOk()
            ->json('props');

        $this->assertEqualsCanonicalizing(
            ['controlledTeam', 'teams', 'bonusCardInventory', 'bonusCardOffers', 'errors', 'flash'],
            array_keys($props)
        );
        $this->assertEquals(800, $props['controlledTeam']['budget']);
        $this->assertEquals(800, collect($props['teams'])->firstWhere('id', $mine->id)['budget']);
        $this->assertCount(1, $props['bonusCardInventory']);
    }
}
