<?php

namespace Tests\Feature;

use App\Services\Cards\BonusCardActivationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\BuildsGameWorld;
use Tests\TestCase;

/**
 * Malus de fatigue et de moral : appliqués à tout l'effectif visé en une seule mise à jour,
 * sans passer sous 0 et sans toucher les autres équipes.
 */
class MalusDrainTest extends TestCase
{
    use RefreshDatabase;
    use BuildsGameWorld;

    /** Émetteur A qui affronte B cette semaine ; C est étrangère au match. */
    private function world(int $squad = 11): array
    {
        $save = $this->makeSave();
        [$a] = $this->makeTeamWithSquad($save, [], $squad);
        [$b, $bPlayers] = $this->makeTeamWithSquad($save, [], $squad);
        [, $cPlayers] = $this->makeTeamWithSquad($save, [], $squad);
        $this->makeMatch($save, $a, $b);

        return [$save->fresh(), $a, $bPlayers, $cPlayers];
    }

    private function malus(string $effect, array $value = []): \App\Models\BonusCard
    {
        return $this->makeBonusCard([
            'kind' => 'malus', 'target' => 'opponent', 'effect_type' => $effect, 'effect_value' => $value,
        ]);
    }

    public function test_morale_drain_hits_the_whole_target_squad_floors_at_zero_and_spares_others(): void
    {
        [$save, $a, $bPlayers, $cPlayers] = $this->world();
        $bPlayers[0]->update(['morale' => 10]);
        foreach ($bPlayers->slice(1) as $p) $p->update(['morale' => 60]);
        $cPlayers[0]->update(['morale' => 60]);
        $card = $this->makeGameCard($save, $a, $this->malus('opponent_morale_drain', ['amount' => 30]));

        $result = app(BonusCardActivationService::class)->activate($card, $save);

        $this->assertSame(0, (int) $bPlayers[0]->fresh()->morale, 'Plancher à 0, pas de dépassement.');
        $this->assertSame(30, (int) $bPlayers[1]->fresh()->morale);
        $this->assertSame(60, (int) $cPlayers[0]->fresh()->morale, 'Une équipe étrangère au match est intacte.');
        $this->assertStringContainsString((string) $bPlayers->count(), $result['message']);
    }

    public function test_stamina_drain_only_hits_the_starters_of_the_target(): void
    {
        [$save, $a, $bPlayers] = $this->world();
        foreach ($bPlayers as $p) $p->update(['stamina' => 80]);
        $bPlayers[0]->update(['stamina' => 5]);
        $card = $this->makeGameCard($save, $a, $this->malus('opponent_stamina_drain'));

        $before = $bPlayers->map(fn ($p) => (int) $p->fresh()->stamina)->all();
        $app = app(BonusCardActivationService::class);
        $app->activate($card, $save);

        $after = $bPlayers->map(fn ($p) => (int) $p->fresh()->stamina)->all();
        $this->assertSame(0, $after[0], 'Plancher à 0.');
        $changed = collect($after)->zip($before)->filter(fn ($pair) => $pair[0] < $pair[1])->count();
        $this->assertGreaterThan(0, $changed);
    }

    public function test_the_number_of_queries_does_not_grow_with_the_squad_size(): void
    {
        $counts = [];
        $n = 0;
        DB::listen(function () use (&$n) { $n++; }); // un seul écouteur : ils s'additionneraient d'un tour à l'autre
        foreach ([11, 22] as $size) {
            [$save, $a] = $this->world($size);
            $card = $this->makeGameCard($save, $a, $this->malus('opponent_morale_drain', ['amount' => 5]));

            $n = 0;
            app(BonusCardActivationService::class)->activate($card, $save);
            $counts[$size] = $n;
        }

        $this->assertSame($counts[11], $counts[22], 'Une mise à jour pour tout l\'effectif, quel que soit son effectif.');
    }
}
