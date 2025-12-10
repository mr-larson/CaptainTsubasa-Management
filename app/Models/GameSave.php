<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GameSave extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'team_id',
        'period',
        'season',
        'week',
        'label',
        'state',
    ];

    protected $casts = [
        'state' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function matches()
    {
        return $this->hasMany(GameMatch::class);
    }

}
