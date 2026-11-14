<?php

namespace Tests\Feature;

use App\Enums\PromiseStatus;
use App\Models\GameSaves\GameDeclaration;
use App\Models\GameSaves\GameInjury;
use App\Models\GameSaves\GameMatch;
use App\Models\GameSaves\GamePromise;
use App\Models\GameSaves\GameSanction;
use App\Services\Cards\BonusCardActivationService;
use App\Services\Players\DeclarationService;
use App\Services\Matches\FoulAndInjuryService;
use App\Services\Matches\MatchSimulator;
use App\Services\Season\SeasonService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsGameWorld;
use Tests\TestCase;

/**
 * Régressions des règles de jeu corrigées (lot 2 de la revue backend) :
 * cumul des jaunes, intersaison, indisponibles en simulation, gardien épargné
 * par les malus, progression IA persistée sur l'instance de l'appelant.
 */
class GameRulesFixesTest extends TestCase
{
    use RefreshDatabase;
    use BuildsGameWorld;

    public function test_yellow_cards_suspend_every_third_card_only(): void
    {
        $save   = $this->makeSave();
        $team   = $this->makeTeam($save);
        $player = $this->makePlayer($save);
        $this->makeContract($save, $team, $player);
        $match  = $this->makeMatch($save, $team, $team, ['status' => 'played']);

        for ($i = 0; $i < 6; $i++) {
            app(FoulAndInjuryService::class)->processMatchEvents($save, $match, [
                ['type' => 'card', 'player_id' => $player->id, 'card_type' => 'yellow'],
            ]);
        }

        $types = GameSanction::where('game_player_id', $player->id)->orderBy('id')->pluck('type')->all();
        $this->assertSame(['yellow', 'yellow', 'double_yellow', 'yellow', 'yellow', 'double_yellow'], $types);
    }

    public function test_new_season_clears_injuries_sanctions_and_expires_pending_promises(): void
    {
        $save   = $this->makeSave(['phase' => 'season_end', 'week' => 14]);
        [$team, $players] = $this->makeTeamWithSquad($save, ['is_controlled' => true, 'human_seat' => 1]);
        $this->makeTeam($save);
        $player = $players->first();

        GameInjury::create([
            'game_save_id' => $save->id, 'game_player_id' => $player->id,
            'severity' => 'severe', 'weeks_out' => 4, 'week_injured' => 13, 'week_return' => 17,
        ]);
        GameSanction::create([
            'game_save_id' => $save->id, 'game_player_id' => $player->id, 'type' => 'red',
            'weeks_suspended' => 2, 'week_match' => 13, 'week_return' => 16, 'yellow_card_count' => 0,
        ]);
        $pending = GamePromise::create([
            'game_save_id' => $save->id, 'game_player_id' => $player->id, 'game_team_id' => $team->id, 'type' => 'starter',
            'status' => 'pending', 'start_week' => 13, 'due_week' => 17, 'target_matches' => 4, 'season' => 1,
        ]);
        $kept = GamePromise::create([
            'game_save_id' => $save->id, 'game_player_id' => $player->id, 'game_team_id' => $team->id, 'type' => 'starter',
            'status' => 'kept', 'start_week' => 2, 'due_week' => 6, 'target_matches' => 4, 'season' => 1,
        ]);

        app(SeasonService::class)->startNewSeason($save);

        $this->assertSame(0, GameInjury::where('game_save_id', $save->id)->count());
        $this->assertSame(0, GameSanction::where('game_save_id', $save->id)->count());
        $this->assertSame(PromiseStatus::Expired, $pending->fresh()->status);
        $this->assertSame(PromiseStatus::Kept, $kept->fresh()->status);
    }

    public function test_declaration_cooldown_does_not_cross_seasons(): void
    {
        $save = $this->makeSave(['season' => 2, 'week' => 1]);
        [$team, $players] = $this->makeTeamWithSquad($save);
        $player = $players->first();

        // Déclaration en semaine 1 de la saison précédente.
        GameDeclaration::create([
            'game_save_id' => $save->id, 'game_player_id' => $player->id, 'type' => 'praise',
            'deserved' => true, 'outcome' => 'well_received', 'week' => 1, 'season' => 1,
        ]);

        $result = app(DeclarationService::class)->declare($save, $player, 'praise', $team->id);

        $this->assertInstanceOf(GameDeclaration::class, $result);
    }

    public function test_simulated_match_replaces_injured_and_suspended_starters(): void
    {
        $save = $this->makeSave(['week' => 3]);
        [$home, $homePlayers] = $this->makeTeamWithSquad($save, [], 14);
        [$away] = $this->makeTeamWithSquad($save);
        $match = $this->makeMatch($save, $home, $away, ['week' => 3]);

        $injured   = $homePlayers[1];
        $suspended = $homePlayers[2];
        GameInjury::create([
            'game_save_id' => $save->id, 'game_player_id' => $injured->id,
            'severity' => 'moderate', 'weeks_out' => 2, 'week_injured' => 2, 'week_return' => 4,
        ]);
        GameSanction::create([
            'game_save_id' => $save->id, 'game_player_id' => $suspended->id, 'type' => 'red',
            'weeks_suspended' => 1, 'week_match' => 2, 'week_return' => 4, 'yellow_card_count' => 0,
        ]);

        app(MatchSimulator::class)->simulateMatchesCollection(GameMatch::whereKey($match->id)->get(), $save);

        $starters = $match->fresh()->match_stats['starters']['home'];
        $this->assertNotContains((string) $injured->id, $starters);
        $this->assertNotContains((string) $suspended->id, $starters);
        $this->assertCount(11, $starters, 'Les absents doivent être remplacés par des remplaçants.');
    }

    public function test_ai_match_progression_survives_the_callers_next_save(): void
    {
        $save = $this->makeSave();
        [$home] = $this->makeTeamWithSquad($save);
        [$away] = $this->makeTeamWithSquad($save);
        $match = $this->makeMatch($save, $home, $away);

        app(MatchSimulator::class)->simulateMatchesCollection(GameMatch::whereKey($match->id)->get(), $save);

        // Le contrôleur sauvegarde ensuite sa propre instance (training IA, shop…).
        $save->save();

        $history = $save->fresh()->state['match_progression_history'] ?? [];
        $this->assertSame([$match->id], array_column($history, 'match_id'));
    }

    public function test_bench_malus_never_targets_a_goalkeeper_stored_as_goalkeeper(): void
    {
        $save   = $this->makeSave();
        $source = $this->makeTeam($save, ['is_controlled' => true]);
        $opponent = $this->makeTeam($save);
        $this->makeMatch($save, $source, $opponent);

        // Seul titulaire de champ possible : tous les autres sont gardiens
        // (position telle que stockée en base : 'Goalkeeper').
        $fielder = $this->makePlayer($save, ['position' => 'Forward']);
        $this->makeContract($save, $opponent, $fielder);
        for ($i = 0; $i < 10; $i++) {
            $this->makeContract($save, $opponent, $this->makePlayer($save, ['position' => 'Goalkeeper']));
        }

        $card = $this->makeBonusCard(['kind' => 'malus', 'target' => 'opponent', 'effect_type' => 'opponent_bench_starter', 'effect_value' => ['pick' => 'random']]);
        $service = app(BonusCardActivationService::class);
        $service->activate($this->makeGameCard($save, $source, $card), $save);

        $this->assertSame([$fielder->id], array_map('intval', $service->getBenchedPlayerIds($save->fresh(), $opponent->id)));
    }
}
