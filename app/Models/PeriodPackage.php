<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Package de période importé par un utilisateur (façon mod) : effectifs,
 * équipes et contrats propres, prêts à remplacer le peuplement standard
 * d'une GameSave à sa création. Contenu lu une seule fois, à l'import
 * (cf. GameSaveController::start()) — jamais requêté isolément, d'où le
 * choix de blobs JSON plutôt que des tables miroir relationnelles.
 */
class PeriodPackage extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'description',
        'is_shared',
        'player_count',
        'team_count',
        'teams_json',
        'players_json',
        'contracts_json',
    ];

    protected $casts = [
        'is_shared'      => 'boolean',
        'teams_json'     => 'array',
        'players_json'   => 'array',
        'contracts_json' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Packages visibles par cet utilisateur : les siens + les partagés, ou
     * tout pour un admin (même bypass que Gate::before / PeriodPackagePolicy).
     */
    public function scopeVisibleTo($query, User $user)
    {
        if ($user->isAdmin()) {
            return $query;
        }

        return $query->where(fn ($q) => $q->where('is_shared', true)->orWhere('user_id', $user->id));
    }
}
