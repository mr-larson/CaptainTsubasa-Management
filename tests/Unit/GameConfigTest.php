<?php

namespace Tests\Unit;

use App\Models\GameSaves\GameSave;
use App\Support\GameConfig;
use Tests\TestCase;

class GameConfigTest extends TestCase
{
    public function test_saved_values_override_defaults_and_difficulty_drives_budgets(): void
    {
        $config = new GameConfig(['match_max_turns' => 45, 'career_difficulty' => 'conquest']);

        $this->assertSame(45, $config->int('match_max_turns'));
        $this->assertTrue($config->bool('bonus_cards_enabled'));                       // défaut
        $this->assertSame(GameConfig::DRAFT_BUDGETS['conquest'], $config->int('draft_budget'));

        $explicit = new GameConfig(['career_difficulty' => 'conquest', 'draft_budget' => 6000]);
        $this->assertSame(6000, $explicit->int('draft_budget'));
    }

    public function test_get_falls_back_to_the_default_for_unknown_saved_key(): void
    {
        $this->assertSame(3, (new GameConfig())->get('training_max_per_week'));
        $this->assertSame('x', (new GameConfig())->get('nope', 'x'));
        $this->assertTrue((new GameConfig())->get('visible_origins.blue_lock'));
    }

    public function test_sanitize_keeps_only_known_keys_and_origins(): void
    {
        $clean = GameConfig::sanitize([
            'match_max_turns' => 30, 'hack' => 1,
            'visible_origins' => ['blue_lock' => false, 'rogue' => true],
        ]);

        $this->assertSame(['match_max_turns' => 30, 'visible_origins' => ['blue_lock' => false]], $clean);
        $this->assertSame([], GameConfig::sanitize(null));
    }

    public function test_rules_are_prefixed_and_cover_every_default_key(): void
    {
        $rules = GameConfig::rules('game_config.');

        foreach (array_keys(GameConfig::DEFAULTS) as $key) {
            $this->assertArrayHasKey("game_config.{$key}", $rules, $key);
        }
    }

    public function test_training_gains_complete_the_missing_bound_from_the_current_config(): void
    {
        $config = new GameConfig(['training_gain_min' => 4, 'training_gain_max' => 6]);

        $this->assertTrue($config->hasValidTrainingGains(['training_gain_max' => 4]));
        $this->assertFalse($config->hasValidTrainingGains(['training_gain_max' => 3]));
        $this->assertFalse($config->hasValidTrainingGains(['training_gain_min' => 7]));
    }

    public function test_options_drive_the_initial_draws(): void
    {
        $off = new GameConfig(['initial_speed_random' => false, 'initial_stamina_random' => false, 'luck_random' => false]);

        $this->assertSame(55, $off->initialSpeed(55));
        $this->assertSame(100, $off->initialStamina());
        $this->assertSame(\App\Helpers\PlayerLuck::NEUTRAL, $off->rollLuck());

        $on = new GameConfig();
        $this->assertSame(60, $on->initialSpeed(50, 10));
        $this->assertSame(100, $on->initialSpeed(95, 10));   // borné à 100
    }

    public function test_game_save_exposes_its_config(): void
    {
        $save = new GameSave(['state' => ['config' => ['match_max_turns' => 40]]]);

        $this->assertSame(40, $save->config()->int('match_max_turns'));
        $this->assertSame(40, $save->getConfig('match_max_turns'));
        $this->assertSame(40, $save->getConfig()['match_max_turns']);
    }
}
