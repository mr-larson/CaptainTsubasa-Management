<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Player extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'first_name',
        'image_path',
        'nationality',
        'birth_date',
        'height',
        'weight',
        'period',
        'current_team_id',
        'stats',
        'positions',
        'special_skills',
        'special_moves',
        'weather_bonus',
        'cost',
        'current_contract_duration',
        'fatigue',
        'injury_risk',
        'is_injured',
    ];

    protected $casts = [
        'stats' => 'array',
        'positions' => 'array',
        'special_skills' => 'array',
        'special_moves' => 'array',
        'weather_bonus' => 'array',
    ];

    public function team()
    {
        return $this->belongsTo(Team::class, 'current_team_id');
    }

    public function sanctions()
    {
        return $this->hasMany(Sanction::class);
    }

    public function isSuspended()
    {
        $activeSanctions = $this->sanctions->where('end_date', '>=', now());
        return $activeSanctions->count() > 0;
    }

    public function suspensionRemaining()
    {
        if ($this->isSuspended()) {
            $latestSanction = $this->sanctions->where('end_date', '>=', now())->sortByDesc('end_date')->first();
            return now()->diffInDays($latestSanction->end_date);
        }
        return 0;
    }
}
