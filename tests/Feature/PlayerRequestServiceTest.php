<?php

namespace Tests\Feature;

use App\Enums\PlayerRequestStatus;
use App\Enums\PromiseStatus;
use App\Models\GameSaves\GamePlayerRequest;
use App\Models\GameSaves\GamePromise;
use App\Models\GameSaves\GameSave;
use App\Models\GameSaves\GameTeam;
use App\Services\Players\PlayerRequestService;
use App\Services\Players\PromiseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsGameWorld;
use Tests\TestCase;

/**
 * Demandes des joueurs (PlayerRequestService) : congé, temps de jeu et souci
 * physique. Accepter puis rompre coûte plus cher que refuser ; les trois choix du
 * souci physique ; demandes ignorées ; réponse automatique de l'IA ; cloisonnement
 * hot-seat côté HTTP.
 */
class PlayerRequestServiceTest extends TestCase
{
    use RefreshDatabase;
    use BuildsGameWorld;

    private PlayerRequestService $service;
    private PromiseService $promises;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service  = app(PlayerRequestService::class);
        $this->promises = app(PromiseService::class);
    }

    /**
     * Save en semaine 3 avec une équipe humaine (qui a la main), un adversaire et un
     * match programmé cette semaine. Le joueur demandeur est titulaire.
     *
     * @return array{0: GameSave, 1: GameTeam, 2: GameTeam, 3: \App\Models\GameSaves\GamePlayer}
     */
    private function world(array $playerAttrs = [], array $teamAttrs = []): array
    {
        $save = $this->makeSave(['week' => 3]);
        $team = $this->makeTeam($save, array_merge(['is_controlled' => true, 'human_seat' => 1, 'budget' => 1000], $teamAttrs));
        $save->update(['controlled_game_team_id' => $team->id]);
        $opponent = $this->makeTeam($save);
        $player = $this->makePlayer($save, array_merge(['morale' => 60, 'coach_affinity' => 0], $playerAttrs));
        $this->makeContract($save, $team, $player);

        return [$save->fresh(), $team, $opponent, $player];
    }

    /** Joue le match de la semaine 3 ; $turns = tours joués par le joueur (0 = au repos). */
    private function playWeekMatch(GameSave $save, GameTeam $team, GameTeam $opponent, int $playerId, int $turns): void
    {
        $this->makeMatch($save, $team, $opponent, [
            'week' => 3,
            'status' => 'played',
            'match_stats' => ['starters' => ['home' => [], 'away' => []], 'playtime' => $turns > 0 ? [$playerId => $turns] : []],
        ]);
    }

    public function test_leave_accepted_and_kept_rewards_the_player(): void
    {
        [$save, $team, $opponent, $player] = $this->world(['stamina' => 30]);
        $request = $this->service->open($save, $team->id, $player, 'leave');

        $result = $this->service->respond($save, $request, 'accept');

        $this->assertInstanceOf(GamePlayerRequest::class, $result);
        $this->assertSame(PlayerRequestStatus::Accepted, $request->fresh()->status);
        $promise = GamePromise::findOrFail($request->fresh()->game_promise_id);
        $this->assertSame('rest', $promise->type);
        $this->assertSame('player', $promise->origin);
        // Rien ne bouge tant que la promesse n'est pas jugée.
        $this->assertEquals(60, $player->fresh()->morale);

        $this->playWeekMatch($save, $team, $opponent, $player->id, 0);
        $this->promises->evaluateForWeek($save, 3);

        $this->assertSame(PromiseStatus::Kept, $promise->fresh()->status);
        $this->assertEquals(65, $player->fresh()->morale);
        $this->assertEquals(5, $player->fresh()->coach_affinity);
    }

    public function test_leave_accepted_then_broken_costs_more_than_declining(): void
    {
        [$save, $team, $opponent, $broken] = $this->world(['stamina' => 30]);
        $declined = $this->makePlayer($save, ['morale' => 60, 'coach_affinity' => 0, 'stamina' => 30]);
        $this->makeContract($save, $team, $declined);

        $this->service->respond($save, $this->service->open($save, $team->id, $broken, 'leave'), 'accept');
        $refusal = $this->service->open($save, $team->id, $declined, 'leave');
        $this->service->respond($save, $refusal, 'decline');

        // Le joueur à qui on a promis du repos joue quand même.
        $this->playWeekMatch($save, $team, $opponent, $broken->id, 12);
        $this->promises->evaluateForWeek($save, 3);

        $this->assertSame(PlayerRequestStatus::Declined, $refusal->fresh()->status);
        $this->assertEquals(56, $declined->fresh()->morale);
        $this->assertEquals(-3, $declined->fresh()->coach_affinity);

        $this->assertEquals(48, $broken->fresh()->morale);
        $this->assertEquals(-12, $broken->fresh()->coach_affinity);
        $this->assertLessThan($declined->fresh()->morale, $broken->fresh()->morale);
        $this->assertLessThan($declined->fresh()->coach_affinity, $broken->fresh()->coach_affinity);
    }

    public function test_playing_time_request_becomes_a_playing_time_promise(): void
    {
        [$save, $team, $opponent, $player] = $this->world(['morale' => 45]);
        $request = $this->service->open($save, $team->id, $player, 'playing_time');

        $this->service->respond($save, $request, 'accept');
        $promise = GamePromise::findOrFail($request->fresh()->game_promise_id);
        $this->assertSame('playing_time', $promise->type);

        $this->playWeekMatch($save, $team, $opponent, $player->id, 2); // moins que les 10 tours promis
        $this->promises->evaluateForWeek($save, 3);

        $this->assertSame(PromiseStatus::Broken, $promise->fresh()->status);
        $this->assertEquals(33, $player->fresh()->morale);
        $this->assertEquals(-12, $player->fresh()->coach_affinity);
    }

    public function test_ailment_rest_creates_a_rest_promise(): void
    {
        [$save, $team, $opponent, $player] = $this->world();
        $request = $this->service->open($save, $team->id, $player, 'ailment');
        $this->assertNotEmpty($request->description);
        $this->assertSame(PlayerRequestService::PHYSIO_COST, $request->cost);

        $this->service->respond($save, $request, 'rest');
        $this->assertSame('rest', $request->fresh()->choice);
        $promise = GamePromise::findOrFail($request->fresh()->game_promise_id);
        $this->assertSame('ailment', $promise->origin);

        $this->playWeekMatch($save, $team, $opponent, $player->id, 0);
        $this->promises->evaluateForWeek($save, 3);

        $this->assertSame(PromiseStatus::Kept, $promise->fresh()->status);
        $this->assertEquals(62, $player->fresh()->morale);
        $this->assertEquals(4, $player->fresh()->coach_affinity);
        $this->assertEquals(1000, $team->fresh()->budget);
    }

    public function test_ailment_physio_costs_money_and_gives_the_best_relation(): void
    {
        [$save, $team, , $player] = $this->world();
        $request = $this->service->open($save, $team->id, $player, 'ailment');

        $this->service->respond($save, $request, 'physio');

        $this->assertSame(PlayerRequestStatus::Accepted, $request->fresh()->status);
        $this->assertSame('physio', $request->fresh()->choice);
        $this->assertNull($request->fresh()->game_promise_id);
        $this->assertEquals(1000 - PlayerRequestService::PHYSIO_COST, $team->fresh()->budget);
        $this->assertEquals(63, $player->fresh()->morale);
        $this->assertEquals(8, $player->fresh()->coach_affinity);
    }

    public function test_ailment_physio_is_refused_without_budget(): void
    {
        [$save, $team, , $player] = $this->world([], ['budget' => 100]);
        $request = $this->service->open($save, $team->id, $player, 'ailment');

        $this->assertIsString($this->service->respond($save, $request, 'physio'));
        $this->assertSame(PlayerRequestStatus::Pending, $request->fresh()->status);
        $this->assertEquals(100, $team->fresh()->budget);
        $this->assertEquals(60, $player->fresh()->morale);
    }

    public function test_ailment_play_without_care_lowers_morale_and_relation(): void
    {
        [$save, $team, , $player] = $this->world();
        $request = $this->service->open($save, $team->id, $player, 'ailment');

        $this->service->respond($save, $request, 'play');

        $this->assertSame('play', $request->fresh()->choice);
        $this->assertEquals(54, $player->fresh()->morale);
        $this->assertEquals(-6, $player->fresh()->coach_affinity);
        $this->assertEquals(1000, $team->fresh()->budget);
    }

    public function test_unanswered_requests_are_ignored_at_week_close(): void
    {
        [$save, $team, , $tired] = $this->world(['stamina' => 30]);
        $hurt = $this->makePlayer($save, ['morale' => 60, 'coach_affinity' => 0]);
        $this->makeContract($save, $team, $hurt);
        $leave = $this->service->open($save, $team->id, $tired, 'leave');
        $ailment = $this->service->open($save, $team->id, $hurt, 'ailment');

        $this->service->expireForWeek($save, 3);

        $this->assertSame(PlayerRequestStatus::Ignored, $leave->fresh()->status);
        $this->assertEquals(56, $tired->fresh()->morale);
        $this->assertEquals(-3, $tired->fresh()->coach_affinity);

        $this->assertSame(PlayerRequestStatus::Ignored, $ailment->fresh()->status);
        $this->assertSame('play', $ailment->fresh()->choice);
        $this->assertEquals(54, $hurt->fresh()->morale);
        $this->assertEquals(-6, $hurt->fresh()->coach_affinity);

        // Une demande close ne peut plus recevoir de réponse.
        $this->assertIsString($this->service->respond($save, $leave->fresh(), 'accept'));
    }

    public function test_rest_cannot_be_granted_once_the_week_match_is_played(): void
    {
        [$save, $team, $opponent, $player] = $this->world(['stamina' => 30]);
        $request = $this->service->open($save, $team->id, $player, 'leave');
        $this->playWeekMatch($save, $team, $opponent, $player->id, 12);

        $this->assertIsString($this->service->respond($save, $request, 'accept'));
        $this->assertSame(PlayerRequestStatus::Pending, $request->fresh()->status);

        $options = collect($this->service->pendingForTeam($save, $team->id)[0]['options'])->keyBy('key');
        $this->assertTrue($options['accept']['disabled']);
    }

    public function test_generation_leaves_human_requests_pending_and_answers_for_ai_teams(): void
    {
        [$save, $team, $opponent, $tired] = $this->world(['stamina' => 30]);
        // Équipe IA : un titulaire épuisé et un remplaçant frais au même poste.
        $aiTired = $this->makePlayer($save, ['stamina' => 20]);
        $this->makeContract($save, $opponent, $aiTired);
        $cover = $this->makePlayer($save);
        $this->makeContract($save, $opponent, $cover, ['is_starter' => false]);
        $this->makeMatch($save, $team, $opponent, ['week' => 3]);

        // Tirages fixés à 30 : pas de souci physique (> 8), congé accordé au tirage (<= 50).
        $service = new class($this->promises) extends PlayerRequestService {
            protected function roll(): int
            {
                return 30;
            }
        };
        $service->generateForWeek($save);

        $human = GamePlayerRequest::where('game_team_id', $team->id)->sole();
        $this->assertSame('leave', $human->type);
        $this->assertSame($tired->id, $human->game_player_id);
        $this->assertSame(PlayerRequestStatus::Pending, $human->status);
        $this->assertCount(1, $service->pendingForTeam($save, $team->id));

        $ai = GamePlayerRequest::where('game_team_id', $opponent->id)->sole();
        $this->assertSame($aiTired->id, $ai->game_player_id);
        $this->assertSame(PlayerRequestStatus::Accepted, $ai->status);
        $this->assertContains($aiTired->id, PromiseService::restingPlayerIds($save, 3));

        // Délai entre deux demandes : rien de nouveau la semaine suivante pour ces joueurs.
        $save->update(['week' => 4]);
        $this->makeMatch($save, $team, $opponent, ['week' => 4]);
        $service->generateForWeek($save->fresh());
        $this->assertSame(2, GamePlayerRequest::count());
    }

    public function test_generation_is_skipped_when_the_option_is_disabled(): void
    {
        [$save, $team, $opponent] = $this->world(['stamina' => 30]);
        $save->update(['state' => ['config' => ['player_requests_enabled' => false]]]);
        $this->makeMatch($save, $team, $opponent, ['week' => 3]);

        $service = new class($this->promises) extends PlayerRequestService {
            protected function roll(): int
            {
                return 1;
            }
        };
        $service->generateForWeek($save->fresh());

        $this->assertSame(0, GamePlayerRequest::count());
    }

    public function test_coach_answers_over_http_and_cannot_answer_for_another_manager(): void
    {
        [$save, $team, , $player] = $this->world();
        $otherHuman = $this->makeTeam($save, ['is_controlled' => true, 'human_seat' => 2]);
        $otherPlayer = $this->makePlayer($save, ['morale' => 60]);
        $this->makeContract($save, $otherHuman, $otherPlayer);

        $mine = $this->service->open($save, $team->id, $player, 'ailment');
        $theirs = $this->service->open($save, $otherHuman->id, $otherPlayer, 'leave');

        $this->actingAs($save->user)
            ->post(route('game-saves.player-requests.respond', [$save, $mine]), ['answer' => 'physio'])
            ->assertSessionHasNoErrors();
        $this->assertSame('physio', $mine->fresh()->choice);

        $this->actingAs($save->user)
            ->post(route('game-saves.player-requests.respond', [$save, $theirs]), ['answer' => 'decline'])
            ->assertSessionHasErrors('player_request');
        $this->assertSame(PlayerRequestStatus::Pending, $theirs->fresh()->status);
        $this->assertEquals(60, $otherPlayer->fresh()->morale);
    }
}
