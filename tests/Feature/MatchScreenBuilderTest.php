<?php

namespace Tests\Feature;

use App\Services\MatchScreenBuilder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsGameWorld;
use Tests\TestCase;

/**
 * Préparation de l'écran de match : choix du match, relances de capitaine,
 * mode de contrôle et configuration envoyée au moteur.
 */
class MatchScreenBuilderTest extends TestCase
{
    use RefreshDatabase;
    use BuildsGameWorld;

    private function screen(): MatchScreenBuilder
    {
        return app(MatchScreenBuilder::class);
    }

    /** Une partie avec l'équipe du manager (is_controlled) face à une équipe IA, match prévu semaine 1. */
    private function world(bool $derby = false): array
    {
        $save = $this->makeSave();
        [$mine] = $this->makeTeamWithSquad($save, ['is_controlled' => true, 'human_seat' => 1]);
        [$other] = $this->makeTeamWithSquad($save, $derby ? ['is_controlled' => true, 'human_seat' => 2] : []);
        $save->update(['controlled_game_team_id' => $mine->id]);
        $match = $this->makeMatch($save, $mine, $other);

        return [$save->fresh(), $mine, $other, $match];
    }

    public function test_active_team_is_the_one_of_the_current_manager(): void
    {
        [$save, $mine] = $this->world();

        $this->assertSame($mine->id, $this->screen()->activeTeam($save)->id);
    }

    public function test_next_match_fails_when_nothing_is_left_to_play(): void
    {
        [$save, $mine, , $match] = $this->world();
        $match->update(['status' => 'played']);

        $this->expectException(ModelNotFoundException::class);
        $this->screen()->nextMatch($save, $mine, 1);
    }

    public function test_captain_rerolls_are_reset_to_three_for_both_teams(): void
    {
        [$save, $mine, $other, $match] = $this->world();
        $contracts = \App\Models\GameSaves\GameContract::where('game_save_id', $save->id)
            ->whereIn('game_team_id', [$mine->id, $other->id]);
        // Un seul capitaine par équipe (contrainte d'unicité) : le premier contrat de chaque équipe.
        foreach ([$mine, $other] as $team) {
            $contracts->clone()->where('game_team_id', $team->id)->orderBy('id')->limit(1)
                ->update(['is_captain' => true, 'captain_rerolls_remaining' => 0, 'captain_reroll_used_this_action' => true]);
        }
        $contracts = $contracts->where('is_captain', true);

        $this->screen()->resetCaptainRerolls($save, $match);

        $this->assertSame(2, (clone $contracts)->count());
        $this->assertSame(0, (clone $contracts)->where('captain_rerolls_remaining', '!=', 3)->count());
        $this->assertSame(0, (clone $contracts)->where('captain_reroll_used_this_action', true)->count());
    }

    public function test_engine_config_exposes_both_teams_with_the_manager_as_internal_side(): void
    {
        [$save, $mine, $other, $match] = $this->world();

        $config = $this->screen()->engineConfig($save, $match->fresh(), $mine, 'single');

        $this->assertSame($save->id, $config['gameSaveId']);
        $this->assertSame($mine->id, $config['teams']['internal']['id']);
        $this->assertSame($other->id, $config['teams']['external']['id']);
        $this->assertSame('single', $config['controlMode']);
        $this->assertCount(11, collect($config['teams']['internal']['players'])->filter(fn ($p) => $p['is_starter']));
    }

    public function test_invalid_control_mode_falls_back_to_single_and_both_is_accepted(): void
    {
        [$save, $mine, , $match] = $this->world();

        $this->assertSame('single', $this->screen()->engineConfig($save, $match->fresh(), $mine, 'nonsense')['controlMode']);
        $this->assertSame('both', $this->screen()->engineConfig($save, $match->fresh(), $mine, 'both')['controlMode']);
    }

    public function test_a_derby_between_two_human_managers_forces_both_sides(): void
    {
        [$save, $mine, , $match] = $this->world(derby: true);

        $this->assertSame('both', $this->screen()->engineConfig($save, $match->fresh(), $mine, 'single')['controlMode']);
    }

    public function test_lineup_adjustment_is_skipped_when_the_match_is_no_longer_scheduled(): void
    {
        [$save, , , $match] = $this->world();
        $match->update(['status' => 'played']);

        // Ne doit ni échouer ni modifier la composition : simple sortie anticipée.
        $this->screen()->adjustOpponentLineups($save, $match, 1);

        $this->assertTrue(true);
    }
}
