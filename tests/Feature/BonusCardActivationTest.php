<?php

namespace Tests\Feature;

use App\Services\BonusCardActivationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\Concerns\BuildsGameWorld;
use Tests\TestCase;

/**
 * Activation des cartes bonus ET malus (BonusCardActivationService, le plus
 * gros service du jeu). Couvre les effets immédiats, les cartes pre_match, les
 * malus ciblant l'adversaire, le bouclier anti-malus, et la consommation de
 * fin de semaine. Très stateful : écrit dans de nombreuses clés de state.
 */
class BonusCardActivationTest extends TestCase
{
    use RefreshDatabase;
    use BuildsGameWorld;

    private BonusCardActivationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(BonusCardActivationService::class);
    }

    // ─────────────────────────── Bonus (self / player / finance) ───────────────────────────

    public function test_stamina_boost_refills_team_and_marks_card_used(): void
    {
        $save = $this->makeSave();
        $team = $this->makeTeam($save, ['is_controlled' => true]);
        $p1   = $this->makePlayer($save, ['stamina' => 80]);
        $p2   = $this->makePlayer($save, ['stamina' => 95]);
        $this->makeContract($save, $team, $p1);
        $this->makeContract($save, $team, $p2);

        $card     = $this->makeBonusCard(['effect_type' => 'stamina_boost', 'effect_value' => ['amount' => 10]]);
        $gameCard = $this->makeGameCard($save, $team, $card);

        $this->service->activate($gameCard, $save);

        $this->assertSame(90, (int) $p1->fresh()->stamina);
        $this->assertSame(100, (int) $p2->fresh()->stamina, 'La stamina est plafonnée à 100.');
        $this->assertSame('used', $gameCard->fresh()->status);
    }

    public function test_revenue_gamble_changes_budget(): void
    {
        $save = $this->makeSave();
        $team = $this->makeTeam($save, ['budget' => 5000]);

        // min == max → gain déterministe.
        $card     = $this->makeBonusCard(['target' => 'finance', 'effect_type' => 'revenue_gamble', 'effect_value' => ['min' => 2000, 'max' => 2000]]);
        $gameCard = $this->makeGameCard($save, $team, $card);

        $result = $this->service->activate($gameCard, $save);

        $this->assertSame(7000, (int) $team->fresh()->budget);
        $this->assertSame(2000, $result['gain']);
    }

    public function test_morale_boost_requires_and_targets_a_player(): void
    {
        $save = $this->makeSave();
        $team = $this->makeTeam($save);
        $player = $this->makePlayer($save, ['morale' => 50]);
        $this->makeContract($save, $team, $player);

        $card     = $this->makeBonusCard(['target' => 'player', 'effect_type' => 'morale_boost', 'effect_value' => ['amount' => 15]]);
        $gameCard = $this->makeGameCard($save, $team, $card);

        $this->service->activate($gameCard, $save, $player->id);

        $this->assertSame(65, (int) $player->fresh()->morale);
    }

    public function test_player_target_card_without_target_is_rejected(): void
    {
        $save = $this->makeSave();
        $team = $this->makeTeam($save);
        $card     = $this->makeBonusCard(['target' => 'player', 'effect_type' => 'morale_boost', 'effect_value' => ['amount' => 15]]);
        $gameCard = $this->makeGameCard($save, $team, $card);

        $this->expectException(ValidationException::class);
        $this->service->activate($gameCard, $save); // aucun joueur cible
    }

    public function test_used_card_cannot_be_activated_again(): void
    {
        $save = $this->makeSave();
        $team = $this->makeTeam($save);
        $card     = $this->makeBonusCard();
        $gameCard = $this->makeGameCard($save, $team, $card, ['status' => 'used']);

        $this->expectException(ValidationException::class);
        $this->service->activate($gameCard, $save);
    }

    // ─────────────────────────── Pre-match ───────────────────────────

    public function test_pre_match_card_is_staged_and_aggregated(): void
    {
        $save = $this->makeSave();
        $team = $this->makeTeam($save);

        $card     = $this->makeBonusCard(['target' => 'match', 'execution_phase' => 'pre_match', 'effect_type' => 'stat_boost', 'effect_value' => ['stat' => 'attack', 'amount' => 12]]);
        $gameCard = $this->makeGameCard($save, $team, $card);

        $this->service->activate($gameCard, $save);

        // Mémorisée dans le state pour MatchSimulator.
        $this->assertCount(1, $save->fresh()->state['active_pre_match_cards']);

        // Agrégée en delta de stat exploitable par le moteur.
        $boosts = $this->service->getPreMatchStatBoosts($save->fresh(), $team->id);
        $this->assertSame(12, $boosts['attack']);
    }

    // ─────────────────────────── Malus (opponent) ───────────────────────────

    public function test_opponent_stamina_drain_hits_next_opponent_starters(): void
    {
        $save = $this->makeSave();
        $source = $this->makeTeam($save, ['is_controlled' => true]);
        [$opponent, $players] = $this->makeTeamWithSquad($save);

        // Match à venir source vs adversaire (cible auto).
        $this->makeMatch($save, $source, $opponent, ['week' => 1]);

        $card     = $this->makeBonusCard(['kind' => 'malus', 'target' => 'opponent', 'effect_type' => 'opponent_stamina_drain', 'effect_value' => ['amount' => 30]]);
        $gameCard = $this->makeGameCard($save, $source, $card);

        $result = $this->service->activate($gameCard, $save);

        $this->assertSame(11, $result['drained'], 'Tous les titulaires adverses sont touchés.');
        $this->assertSame(70, (int) $players->first()->fresh()->stamina); // 100 - 30
    }

    public function test_opponent_bench_starter_registers_pending_malus(): void
    {
        $save = $this->makeSave();
        $source = $this->makeTeam($save, ['is_controlled' => true]);
        [$opponent] = $this->makeTeamWithSquad($save);
        $this->makeMatch($save, $source, $opponent, ['week' => 1]);

        $card     = $this->makeBonusCard(['kind' => 'malus', 'target' => 'opponent', 'effect_type' => 'opponent_bench_starter', 'effect_value' => ['pick' => 'key']]);
        $gameCard = $this->makeGameCard($save, $source, $card);

        $this->service->activate($gameCard, $save);
        $save->refresh();

        $this->assertCount(1, $save->state['pending_malus']);
        $benched = $this->service->getBenchedPlayerIds($save, $opponent->id);
        $this->assertCount(1, $benched);
        $this->assertSame($save->state['pending_malus'][0]['target_player_id'], $benched[0]);
    }

    public function test_malus_shield_blocks_an_incoming_malus_once(): void
    {
        $save = $this->makeSave();
        $source = $this->makeTeam($save, ['is_controlled' => true]);
        [$opponent, $players] = $this->makeTeamWithSquad($save);
        $this->makeMatch($save, $source, $opponent, ['week' => 1]);

        // L'adversaire pose un bouclier.
        $shieldCard = $this->makeBonusCard(['effect_type' => 'malus_shield', 'effect_value' => []]);
        $this->service->activate($this->makeGameCard($save, $opponent, $shieldCard), $save);
        $this->assertSame([$opponent->id], $save->fresh()->state['malus_shields']);

        // Le malus est annulé et le bouclier consommé.
        $malusCard = $this->makeBonusCard(['kind' => 'malus', 'target' => 'opponent', 'effect_type' => 'opponent_stamina_drain', 'effect_value' => ['amount' => 30]]);
        $result = $this->service->activate($this->makeGameCard($save, $source, $malusCard), $save);

        $this->assertTrue($result['blocked'] ?? false);
        $this->assertSame(100, (int) $players->first()->fresh()->stamina, 'Stamina intacte : malus paré.');
        $this->assertSame([], $save->fresh()->state['malus_shields'], 'Le bouclier est consommé.');
    }

    public function test_pending_malus_is_consumed_after_target_plays(): void
    {
        $save = $this->makeSave();
        $source = $this->makeTeam($save, ['is_controlled' => true]);
        [$opponent] = $this->makeTeamWithSquad($save);
        $match = $this->makeMatch($save, $source, $opponent, ['week' => 1]);

        $card = $this->makeBonusCard(['kind' => 'malus', 'target' => 'opponent', 'effect_type' => 'opponent_bench_starter', 'effect_value' => ['pick' => 'random']]);
        $this->service->activate($this->makeGameCard($save, $source, $card), $save);
        $this->assertCount(1, $save->fresh()->state['pending_malus']);

        // La cible joue son match...
        $match->update(['status' => 'played', 'home_score' => 1, 'away_score' => 0]);
        $this->service->consumeMalusForPlayedWeek($save->fresh(), 1);

        $this->assertSame([], $save->fresh()->state['pending_malus'], 'Le malus est purgé une fois le match joué.');
    }

    public function test_opponent_malus_without_upcoming_match_is_rejected(): void
    {
        $save = $this->makeSave();
        $source = $this->makeTeam($save, ['is_controlled' => true]);
        $this->makeTeamWithSquad($save); // adversaire potentiel mais aucun match programmé

        $card = $this->makeBonusCard(['kind' => 'malus', 'target' => 'opponent', 'effect_type' => 'opponent_stamina_drain', 'effect_value' => ['amount' => 30]]);

        $this->expectException(ValidationException::class);
        $this->service->activate($this->makeGameCard($save, $source, $card), $save);
    }
}
