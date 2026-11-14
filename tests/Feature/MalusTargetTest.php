<?php

namespace Tests\Feature;

use App\Services\Cards\BonusCardActivationService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\Concerns\BuildsGameWorld;
use Tests\TestCase;

/**
 * Cible d'un malus « à équipe choisie » : le leader du classement quand l'émetteur est l'IA,
 * erreur pour un humain sans cible, et pas de relecture inutile des équipes.
 */
class MalusTargetTest extends TestCase
{
    use RefreshDatabase;
    use BuildsGameWorld;

    /** Humain H, équipes IA A, B (5 victoires) et C (2 victoires). @return array{0: \App\Models\GameSaves\GameSave, 1: array} */
    private function league(): array
    {
        $save = $this->makeSave();
        $h = $this->makeTeam($save, ['is_controlled' => true, 'human_seat' => 1, 'budget' => 1000]);
        $a = $this->makeTeam($save, ['budget' => 1000]);
        $b = $this->makeTeam($save, ['budget' => 1000, 'wins' => 5, 'name' => 'Les Champions']);
        $c = $this->makeTeam($save, ['budget' => 1000, 'wins' => 2]);
        $save->update(['controlled_game_team_id' => $h->id]);

        return [$save->fresh(), compact('h', 'a', 'b', 'c')];
    }

    private function fiscalCard(string $mode = 'select'): \App\Models\BonusCard
    {
        return $this->makeBonusCard([
            'kind' => 'malus', 'target' => 'team', 'effect_type' => 'opponent_budget_drain',
            'effect_value' => ['amount' => 100, 'target_mode' => $mode],
        ]);
    }

    public function test_an_ai_team_without_explicit_target_hits_the_standings_leader_but_not_itself(): void
    {
        [$save, $t] = $this->league();
        $card = $this->makeGameCard($save, $t['a'], $this->fiscalCard('select'));

        $result = app(BonusCardActivationService::class)->activate($card, $save);

        $this->assertSame(900, (int) $t['b']->fresh()->budget, 'Le leader (5 victoires) perd 100.');
        $this->assertSame(1000, (int) $t['c']->fresh()->budget);
        $this->assertSame(1000, (int) $t['a']->fresh()->budget, "L'émetteur n'est jamais sa propre cible.");
        $this->assertStringContainsString('Les Champions', $result['message']);
    }

    public function test_the_leader_mode_skips_the_sender_when_it_is_the_leader(): void
    {
        [$save, $t] = $this->league();
        $card = $this->makeGameCard($save, $t['b'], $this->fiscalCard('leader')); // B est elle-même leader

        app(BonusCardActivationService::class)->activate($card, $save);

        $this->assertSame(900, (int) $t['c']->fresh()->budget, 'Le suivant au classement (2 victoires) est visé.');
        $this->assertSame(1000, (int) $t['b']->fresh()->budget);
    }

    public function test_a_human_must_choose_a_target_team(): void
    {
        [$save, $t] = $this->league();
        $card = $this->makeGameCard($save, $t['h'], $this->fiscalCard('select'));

        try {
            app(BonusCardActivationService::class)->activate($card, $save);
            $this->fail('Une erreur de validation était attendue.');
        } catch (ValidationException $e) {
            $this->assertSame('Vous devez choisir une équipe cible.', $e->errors()['card'][0]);
        }
        $this->assertSame(1000, (int) $t['b']->fresh()->budget);
    }

    public function test_the_target_team_is_read_once_not_three_times(): void
    {
        [$save, $t] = $this->league();
        $card = $this->makeGameCard($save, $t['a'], $this->fiscalCard('select'));
        $card->load('bonusCard'); // la fiche de la carte est déjà connue de l'appelant

        $teamReads = 0;
        DB::listen(function ($query) use (&$teamReads) {
            if (preg_match('/^\s*select\b.*from `game_teams`/is', $query->sql)) {
                $teamReads++;
            }
        });
        app(BonusCardActivationService::class)->activate($card, $save);

        $this->assertSame(1, $teamReads, 'Une seule lecture des équipes : leader et nom de la cible partagent la même.');
    }
}
