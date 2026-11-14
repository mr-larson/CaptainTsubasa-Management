<?php

namespace Tests\Feature;

use App\Services\Cards\BonusCardActivationService;
use App\Services\Cards\CardEffectRegistry;
use Database\Seeders\BonusCardSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\Concerns\BuildsGameWorld;
use Tests\TestCase;

/** Chaque type d'effet du catalogue de cartes a un handler ; un type inconnu est refusé sans consommer la carte. */
class CardEffectRegistryTest extends TestCase
{
    use RefreshDatabase;
    use BuildsGameWorld;

    public function test_every_catalogue_effect_type_has_a_handler(): void
    {
        $registry = app(CardEffectRegistry::class);
        $types = collect(BonusCardSeeder::cards())->pluck('effect_type')->unique();

        $this->assertNotEmpty($types);
        foreach ($types as $type) {
            $this->assertNotNull($registry->for($type), "Aucun handler pour l'effet « {$type} ».");
        }
    }

    public function test_unknown_effect_type_is_refused_and_card_stays_available(): void
    {
        $save = $this->makeSave();
        [$team] = $this->makeTeamWithSquad($save);
        $card = $this->makeBonusCard(['effect_type' => 'does_not_exist']);
        $owned = $this->makeGameCard($save, $team, $card);

        try {
            app(BonusCardActivationService::class)->activate($owned, $save);
            $this->fail('Un type d\'effet inconnu doit être refusé.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('card', $e->errors());
        }

        $this->assertSame('available', $owned->fresh()->status);
    }
}
