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
        'first_name',
        'last_name',
        'age',
        'position',
        'cost',
        'stats',
    ];

    protected $casts = [
        'stats' => 'array',
    ];

    // Relation avec Team via la table pivot Contract
    public function teams()
    {
        return $this->belongsToMany(Team::class, 'contracts');
    }
}
