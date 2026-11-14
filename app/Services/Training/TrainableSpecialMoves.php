<?php

namespace App\Services\Training;

use App\Models\Player;

/** Catalogue des techniques spéciales qu'un joueur peut apprendre en formation : un petit ensemble de techniques de joueurs existants, plus le centre enroulé propre à l'entraînement. */
class TrainableSpecialMoves
{
    /** Coût indicatif d'une formation à une technique spéciale (en GL). */
    public const COST = 300;

    /** Clés des techniques de joueurs existants qui peuvent être enseignées. */
    private const TEACHABLE = [
        'ohtomo_link_play',
        'motoki_clinical_finish',
        'asari_overlap_run',
        'togashi_iron_marking',
        'takeshima_wall_block',
        'ash_libero_cover',
        'sakai_brave_save',
    ];

    /** Liste triée par nom des techniques apprenables : celles de la liste TEACHABLE trouvées chez les joueurs de référence, plus le centre enroulé ajouté ici. */
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

    /** Action de jeu à laquelle une technique se rattache (mains → hand_save, poing → punch_save, sinon son base_action) ; sert à interdire deux techniques pour la même action. */
    public static function actionOf(array $move): ?string
    {
        return match ($move['base_action'] ?? null) {
            'hands' => 'hand_save',
            'punch' => 'punch_save',
            default => $move['base_action'] ?? null,
        };
    }
}
