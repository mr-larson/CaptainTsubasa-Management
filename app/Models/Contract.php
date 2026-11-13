<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Contrat de RÉFÉRENCE du catalogue : relie un joueur du catalogue à une équipe du
 * catalogue (effectifs d'origine du jeu, semés par ContractSeeder). Ne pas confondre
 * avec GameContract, le contrat d'un joueur dans une partie en cours.
 */
class Contract extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'contracts';

    protected $fillable = [
        'team_id',
        'player_id',
        'salary',
        'matches_total',
        'matches_played',
        'start_date',
        'end_date',
        'is_starter',
        'is_captain',
    ];

    protected $casts = [
        'start_date'     => 'date',
        'end_date'       => 'date',
        'matches_total'  => 'integer',
        'matches_played' => 'integer',
        'is_starter'     => 'boolean',
        'is_captain'     => 'boolean',
    ];

    /** Équipe du catalogue qui emploie le joueur. */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /** Joueur du catalogue sous contrat. */
    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    /** Nombre de matchs restant à jouer sur le contrat (jamais négatif). */
    public function getMatchesRemainingAttribute(): int
    {
        return max(0, ($this->matches_total ?? 0) - ($this->matches_played ?? 0));
    }

    /** Le contrat est-il terminé ? Vrai quand tous les matchs prévus ont été joués. */
    public function isExpired(): bool
    {
        return ($this->matches_played ?? 0) >= ($this->matches_total ?? 0);
    }

    /** Le contrat est-il encore en cours ? Contraire de isExpired(). */
    public function isCurrent(): bool
    {
        return ! $this->isExpired();
    }

    /** Synonyme de isExpired(), pour une lecture plus naturelle à l'appel. */
    public function hasEnded(): bool
    {
        return $this->isExpired();
    }
}
