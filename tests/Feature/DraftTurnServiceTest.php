<?php

namespace Tests\Feature;

use App\Models\GameSaves\GameSave;
use App\Models\User;
use App\Services\Draft\DraftService;
use App\Services\Draft\DraftTurnService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsGameWorld;
use Tests\TestCase;

/**
 * Actions d'un tour de draft (IA, humain, clôture), testées sur le service puis
 * sur les routes JSON : garde-fous de phase et de tour, refus et réponses.
 */
class DraftTurnServiceTest extends TestCase
{
    use RefreshDatabase;
    use BuildsGameWorld;

    private function service(): DraftTurnService
    {
        return app(DraftTurnService::class);
    }

    /**
     * Une draft à deux équipes : un manager humain et une équipe IA, avec des joueurs
     * libres. $humanFirst choisit qui est « sur l'horloge » au premier tour.
     */
    private function draft(bool $humanFirst): array
    {
        $save    = $this->makeSave(['phase' => 'draft']);
        $human   = $this->makeTeam($save, ['is_controlled' => true, 'human_seat' => 1, 'budget' => 100000]);
        $ai      = $this->makeTeam($save, ['budget' => 100000]);
        $save->update(['controlled_game_team_id' => $human->id]);
        foreach (range(1, 6) as $_) {
            $this->makePlayer($save, ['cost' => 100]);
        }

        app(DraftService::class)->initDraft($save->fresh(), $humanFirst ? [$human->id, $ai->id] : [$ai->id, $human->id]);

        return [$save->fresh(), $human, $ai];
    }

    public function test_every_action_is_refused_outside_the_draft_phase(): void
    {
        [$save] = $this->draft(true);
        $save->update(['phase' => 'season']);
        $save = $save->fresh();

        foreach ([
            $this->service()->playAiTurn($save),
            $this->service()->playHumanPick($save, 1),
            $this->service()->finishHumanDraft($save),
        ] as $result) {
            $this->assertSame(400, $result->status);
            $this->assertSame('Not in draft phase', $result->body['error']);
        }
    }

    public function test_the_ai_cannot_play_a_human_turn_and_the_human_cannot_play_an_ai_turn(): void
    {
        [$save] = $this->draft(true);
        $this->assertSame('It is your turn to pick', $this->service()->playAiTurn($save)->body['error']);

        [$save] = $this->draft(false);
        $this->assertSame('Not your turn', $this->service()->playHumanPick($save, 1)->body['error']);
    }

    public function test_an_ai_turn_either_picks_a_player_or_closes_the_team_draft(): void
    {
        [$save] = $this->draft(false);

        $result = $this->service()->playAiTurn($save);

        $this->assertSame(200, $result->status);
        $this->assertArrayHasKey('draftState', $result->body);
        $this->assertTrue(
            ($result->body['pick'] ?? null) !== null || ($result->body['finished'] ?? false),
            'Le tour IA doit produire un pick ou clore le draft de l\'équipe.'
        );
    }

    public function test_an_impossible_human_pick_is_refused_with_422(): void
    {
        [$save] = $this->draft(true);

        $result = $this->service()->playHumanPick($save, 999999); // joueur inexistant

        $this->assertSame(422, $result->status);
        $this->assertStringContainsString('Sélection impossible', $result->body['error']);
    }

    public function test_a_valid_human_pick_signs_the_player_and_passes_the_turn(): void
    {
        [$save, $human] = $this->draft(true);
        $player = $save->gamePlayers()->first();

        $result = $this->service()->playHumanPick($save, $player->id);

        $this->assertSame(200, $result->status);
        $this->assertNotNull($result->body['pick']);
        $this->assertSame(1, \App\Models\GameSaves\GameContract::where('game_player_id', $player->id)->where('game_team_id', $human->id)->count());
    }

    public function test_json_routes_answer_with_the_service_status_and_reject_strangers(): void
    {
        [$save] = $this->draft(true);
        $owner = User::find($save->user_id);

        // Tour d'un humain : l'action IA répond 400 avec le message du service.
        $this->actingAs($owner)->postJson(route('game-saves.draft.ai-pick', $save))
            ->assertStatus(400)->assertJson(['error' => 'It is your turn to pick']);

        // Un pick impossible répond 422.
        $this->actingAs($owner)->postJson(route('game-saves.draft.pick', $save), ['player_id' => 999999])
            ->assertStatus(422);

        // Un autre utilisateur n'a aucun droit sur cette partie.
        $this->actingAs(User::factory()->create())->postJson(route('game-saves.draft.ai-pick', $save))->assertForbidden();
    }
}
