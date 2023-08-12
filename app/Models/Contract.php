<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Contract extends Model
{
    use HasFactory;
    Use SoftDeletes;

    protected $fillable = [
        'team_id', //unsignedBigInteger, équipe concernée par le contrat
        'player_id', //unsignedBigInteger, joueur concerné par le contrat
        'start_date', //date
        'end_date', //date
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function player()
    {
        return $this->belongsTo(Player::class);
    }

    // Une méthode pour déterminer si un contrat est actuellement actif
    public function isActive()
    {
        $today = now()->toDateString();
        return $this->start_date <= $today && $this->end_date >= $today;
    }
}
