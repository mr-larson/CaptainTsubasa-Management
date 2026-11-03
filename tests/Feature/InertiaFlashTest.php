<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Les messages flash des contrôleurs sont partagés avec Inertia pour le
 * toast global (AuthenticatedLayout).
 */
class InertiaFlashTest extends TestCase
{
    use RefreshDatabase;

    public function test_flash_messages_are_shared_with_inertia(): void
    {
        $this->actingAs(User::factory()->create())
            ->withSession(['success' => 'Joueur mis à jour.', 'message' => 'Aucune partie existante.'])
            ->get(route('game-saves.index'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('flash.success', 'Joueur mis à jour.')
                ->where('flash.info', 'Aucune partie existante.')
                ->missing('flash.error')
            );
    }
}
