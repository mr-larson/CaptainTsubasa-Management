<?php

namespace Tests\Feature;

use App\Models\Team;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\Concerns\BuildsGameWorld;
use Tests\TestCase;

/** Durcissements issus de l'audit des middleware, policies et requests. */
class AuthorizationHardeningTest extends TestCase
{
    use RefreshDatabase;
    use BuildsGameWorld;

    public function test_reference_data_policies_are_admin_only(): void
    {
        $player = User::factory()->create();
        $admin  = User::factory()->create();
        $admin->forceFill(['is_admin' => true])->save();

        $this->assertFalse($player->can('viewAny', Team::class));
        $this->assertFalse($player->can('create', Team::class));
        $this->assertTrue($admin->can('viewAny', Team::class));

        $this->actingAs($player)->get(route('teams.index'))->assertForbidden();
        $this->actingAs($admin)->get(route('teams.index'))->assertOk();
    }

    public function test_password_reset_link_is_built_from_app_url_not_the_request_host(): void
    {
        Notification::fake();
        config(['app.url' => 'https://goldeneleven.test']);
        $user = User::factory()->create();

        $this->withHeader('Host', 'evil.example')
            ->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use ($user) {
            $url = $notification->toMail($user)->actionUrl;

            return str_starts_with($url, 'https://goldeneleven.test/reset-password/')
                && ! str_contains($url, 'evil.example');
        });
    }

    public function test_short_save_url_redirects_to_the_play_page(): void
    {
        $save = $this->makeSave();

        $this->actingAs(User::find($save->user_id))
            ->get(route('game-saves.show', $save))
            ->assertRedirect(route('game-saves.Play', $save));

        $this->actingAs(User::factory()->create())
            ->get(route('game-saves.show', $save))
            ->assertForbidden();
    }

    public function test_renaming_a_save_only_changes_its_label(): void
    {
        $save = $this->makeSave();
        $week = $save->week;

        $this->actingAs(User::find($save->user_id))
            ->put(route('game-saves.update', $save), ['label' => 'Ma carrière', 'week' => 99, 'period' => 'pro'])
            ->assertSessionHasNoErrors();

        $save->refresh();
        $this->assertSame('Ma carrière', $save->label);
        $this->assertSame($week, $save->week);
    }
}
