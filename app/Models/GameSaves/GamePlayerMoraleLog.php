<?php

namespace App\Models\GameSaves;

use App\Enums\MoraleSource;
use App\Models\GameSaves\Concerns\BelongsToGameSave;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Journal des variations de moral d'un joueur : source (résultat, temps de jeu,
 * salaire…), valeur signée, libellé, semaine et saison. Il alimente l'historique
 * affiché dans l'onglet Moral ; les saisons anciennes sont purgées à l'intersaison.
 */
class GamePlayerMoraleLog extends Model
{
    use BelongsToGameSave;

    protected $fillable = [
        'game_save_id',
        'game_player_id',
        'source',
        'value',
        'label',
        'week',
        'season',
    ];

    protected $casts = [
        'source' => MoraleSource::class,
        'value'  => 'integer',
        'week'   => 'integer',
        'season' => 'integer',
    ];

    /** Joueur dont le moral a varié. */
    public function gamePlayer(): BelongsTo
    {
        return $this->belongsTo(GamePlayer::class);
    }
}
