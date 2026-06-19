<?php

namespace Tests\Feature;

use App\Models\GameSaves\GameSave;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(bool $admin = false): User
    {
        $user = User::factory()->create();
        $user->is_admin = $admin; // hors $fillable : set explicite
        $user->save();

        return $user;
    }

    private function makeSave(User $owner): GameSave
    {
        return GameSave::create([
            'user_id'   => $owner->id,
            'team_id'   => null,
            'period'    => 'college',
            'season'    => 1,
            'week'      => 1,
            'phase'     => 'season',
            'game_mode' => 'prebuilt',
            'state'     => null,
        ]);
    }

    // ───────────────────────── Ownership des sauvegardes ─────────────────────────

    public function test_owner_can_update_own_game_save(): void
    {
        $owner = $this->makeUser();
        $save  = $this->makeSave($owner);

        $this->actingAs($owner)
            ->put(route('game-saves.update', $save), ['label' => 'Ma partie'])
            ->assertRedirect();

        $this->assertDatabaseHas('game_saves', ['id' => $save->id, 'label' => 'Ma partie']);
    }

    public function test_stranger_cannot_view_another_users_game_save(): void
    {
        $owner    = $this->makeUser();
        $stranger = $this->makeUser();
        $save     = $this->makeSave($owner);

        $this->actingAs($stranger)
            ->get(route('game-saves.Play', $save))
            ->assertForbidden();
    }

    public function test_stranger_cannot_delete_another_users_game_save(): void
    {
        $owner    = $this->makeUser();
        $stranger = $this->makeUser();
        $save     = $this->makeSave($owner);

        $this->actingAs($stranger)
            ->delete(route('game-saves.destroy', $save))
            ->assertForbidden();

        $this->assertDatabaseHas('game_saves', ['id' => $save->id]);
    }

    public function test_admin_can_act_on_any_game_save(): void
    {
        $owner = $this->makeUser();
        $admin = $this->makeUser(admin: true);
        $save  = $this->makeSave($owner);

        $this->actingAs($admin)
            ->delete(route('game-saves.destroy', $save))
            ->assertRedirect();

        $this->assertDatabaseMissing('game_saves', ['id' => $save->id]);
    }

    public function test_guest_is_redirected_from_game_save(): void
    {
        $owner = $this->makeUser();
        $save  = $this->makeSave($owner);

        $this->get(route('game-saves.Play', $save))->assertRedirect(route('login'));
    }

    // ───────────────────── Données de référence : admin uniquement ─────────────────────

    public function test_non_admin_cannot_open_reference_data_screens(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)->get(route('teams.create'))->assertForbidden();
        $this->actingAs($user)->get(route('players.edit'))->assertForbidden();
        $this->actingAs($user)->get(route('contracts.create'))->assertForbidden();
    }

    public function test_non_admin_cannot_write_reference_data(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)->post(route('teams.store'), [])->assertForbidden();
        $this->actingAs($user)->post(route('players.store'), [])->assertForbidden();
        $this->actingAs($user)->post(route('contracts.store'), [])->assertForbidden();
    }

    public function test_admin_can_open_reference_data_screens(): void
    {
        $admin = $this->makeUser(admin: true);

        $this->actingAs($admin)->get(route('teams.create'))->assertOk();
        $this->actingAs($admin)->get(route('contracts.create'))->assertOk();
    }

    public function test_guest_is_redirected_from_reference_data(): void
    {
        $this->get(route('teams.create'))->assertRedirect(route('login'));
    }
}
