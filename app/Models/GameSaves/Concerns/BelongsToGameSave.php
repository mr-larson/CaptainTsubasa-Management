<?php

namespace App\Models\GameSaves\Concerns;

use App\Models\GameSaves\GameSave;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Mutualise la relation vers la sauvegarde parente pour tous les
 * modèles rattachés à un game_save_id.
 */
trait BelongsToGameSave
{
    public function gameSave(): BelongsTo
    {
        return $this->belongsTo(GameSave::class);
    }
}
