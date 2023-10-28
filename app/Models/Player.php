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
        'name', //string
        'first_name', //string
        'image_path', //chemin vers l'image
        'nationality', //string
        'birth_date', //date
        'height', //integer
        'weight', //integer
        'favorite_number', //integer
        'period', //enum
        'stats', //json array
        'positions', //json array
        'special_skills', //json array
        'special_moves', //json array
        'weather_bonus', //json array
        'cost', //integer
        'current_contract_duration', //integer
        'fatigue', //integer
        'injury_risk', //float (0-100)
        'is_injured', //boolean
        'description', //string
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

    public function trainings()
    {
        return $this->belongsToMany(Training::class, 'trainings_players', 'player_id', 'training_id');
    }

    public function sanctions()
    {
        return $this->hasMany(Sanction::class);
    }

    public function injuries()
    {
        return $this->hasMany(Injury::class);
    }

    public function isCurrentlyInjured()
    {
        return $this->injuries->where('isActive', true)->count() > 0;
    }

    public function injuryDaysRemaining()
    {
        if ($this->isCurrentlyInjured()) {
            $latestInjury = $this->injuries->sortByDesc('injury_date')->first();
            return now()->diffInDays($latestInjury->injury_date->addDays($latestInjury->duration_in_days), false);
        }
        return 0;
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

    public function increaseFatigue($value)
    {
        $this->fatigue += $value;
        $this->checkForInjury();
        $this->save();
    }

    public function decreaseFatigue($value)
    {
        $this->fatigue -= max(0, $this->fatigue - $value);
        $this->checkForInjury();
        $this->save();
    }



    const INJURY_THRESHOLD = 90; // exemple de valeur
    const RISK_VALUE = 80;      // exemple de valeur
    private function checkForInjury(): void
    {
        if ($this->fatigue > self::INJURY_THRESHOLD && $this->injury_risk > self::RISK_VALUE) {
            $this->is_injured = true;
        }
    }

}
