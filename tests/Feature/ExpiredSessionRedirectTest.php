<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Session expirée : une action Inertia non-GET doit rediriger vers /login en 303, pas en 302. */
class ExpiredSessionRedirectTest extends TestCase
{
    use RefreshDatabase;

    private const INERTIA = ['X-Inertia' => 'true', 'X-Requested-With' => 'XMLHttpRequest', 'Accept' => 'text/html, application/xhtml+xml'];

    public function test_a_guest_inertia_patch_is_redirected_to_login_with_303(): void
    {
        $this->withHeaders(self::INERTIA)->patch('/profile/preferences', ['pixel_art' => true])
            ->assertStatus(303)->assertRedirect(route('login'));
    }

    public function test_a_guest_page_visit_keeps_the_regular_redirect(): void
    {
        $this->get('/dashboard')->assertStatus(302)->assertRedirect(route('login'));
    }
}
