<?php

namespace App\Models\GameSaves;

use Illuminate\Database\Eloquent\Model;

/**
 * Déroulé d'un match (ex-match_stats.events), 1-1 avec game_matches.
 * C'est la partie la plus lourde d'un match : elle n'est chargée que pour la
 * feuille de match / le replay, jamais par les requêtes de calendrier.
 */
class GameMatchEventLog extends Model
{
    protected $table = 'game_match_events';

    public $timestamps = false;

    protected $guarded = ['id'];

    protected $casts = [
        'events' => 'array',
    ];
}
