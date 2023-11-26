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

}
