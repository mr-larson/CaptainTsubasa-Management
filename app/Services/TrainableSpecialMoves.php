<?php

namespace App\Services;

use App\Models\Player;

class TrainableSpecialMoves
{
    public const COST = 300;

    private const TEACHABLE = [
        'ohtomo_link_play',
        'motoki_clinical_finish',
        'asari_overlap_run',
        'togashi_iron_marking',
        'takeshima_wall_block',
        'ash_libero_cover',
        'sakai_brave_save',
    ];

    public static function catalog(): array
    {
        $moves = Player::query()->whereNotNull('special_moves')->get(['special_moves'])
            ->flatMap(fn (Player $player) => $player->special_moves ?? [])
            ->filter(fn ($move) => in_array($move['key'] ?? null, self::TEACHABLE, true))
            ->keyBy('key');

        $moves->put('training_curled_cross', [
            'key' => 'training_curled_cross',
            'label' => 'Centre enroulé',
            'short_label' => 'Enroulé',
            'mode' => 'attack',
            'base_action' => 'cross',
            'cooldown' => 3,
            'description' => 'Un centre à effet qui rend le dégagement de la tête plus difficile.',
        ]);

        return $moves->sortBy('label')->values()->all();
    }

    public static function actionOf(array $move): ?string
    {
        return match ($move['base_action'] ?? null) {
            'hands' => 'hand_save',
            'punch' => 'punch_save',
            default => $move['base_action'] ?? null,
        };
    }
}
