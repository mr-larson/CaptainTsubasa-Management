<?php

namespace Tests\Feature;

use App\Models\GameSaves\GameContract;
use App\Models\GameSaves\GamePlayer;
use App\Models\GameSaves\GameTeam;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsGameWorld;
use Tests\TestCase;

/**
 * Règles d'intégrité garanties par la base (contraintes uniques), pas
 * seulement par le code.
 */
class IntegrityConstraintsTest extends TestCase
{
    use RefreshDatabase;
    use BuildsGameWorld;

    public function test_base_player_and_team_appear_once_per_save_but_not_across_saves(): void
    {
        $save  = $this->makeSave();
        $other = $this->makeSave();
        $base  = \App\Models\Player::factory()->create();
        $btm   = \App\Models\Team::factory()->create();

        $player = fn ($s) => $this->makePlayer($s, ['base_player_id' => $base->id]);
        $team   = fn ($s) => $this->makeTeam($s, ['base_team_id' => $btm->id]);

        $player($save); $team($save);
        $player($other); $team($other);          // autre sauvegarde : autorisé
        $this->makePlayer($save); $this->makeTeam($save);   // sans référence : autorisé (NULL)

        $this->expectException(UniqueConstraintViolationException::class);
        $player($save);
    }

    public function test_team_base_duplicate_is_rejected(): void
    {
        $save = $this->makeSave();
        $btm  = \App\Models\Team::factory()->create();
        $this->makeTeam($save, ['base_team_id' => $btm->id]);

        $this->expectException(UniqueConstraintViolationException::class);
        $this->makeTeam($save, ['base_team_id' => $btm->id]);
    }

    public function test_one_captain_per_team_and_season_and_one_contract_start_per_player(): void
    {
        $save = $this->makeSave();
        $team = $this->makeTeam($save);
        [$a, $b] = [$this->makePlayer($save), $this->makePlayer($save)];

        $this->makeContract($save, $team, $a, ['is_captain' => true]);
        $this->makeContract($save, $team, $b, ['is_captain' => false]);
        $this->makeContract($save, $team, $this->makePlayer($save), ['is_captain' => false]);   // plusieurs non-capitaines : autorisé

        // Renouvellement : nouveau contrat à une autre semaine de départ.
        $this->makeContract($save, $team, $b, ['start_week' => 15]);

        try {
            $this->makeContract($save, $team, $this->makePlayer($save), ['is_captain' => true]);
            $this->fail('Deux capitaines dans la même équipe et saison.');
        } catch (UniqueConstraintViolationException) {
            $this->assertTrue(true);
        }

        $this->expectException(UniqueConstraintViolationException::class);
        $this->makeContract($save, $team, $b);   // même joueur, même semaine de départ
    }
}
