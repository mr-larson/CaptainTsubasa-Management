<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TrainingType extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'stat_increase',
    ];

    protected $casts = [
        'stat_increase' => 'array',
    ];

    /**
     * Relation vers les entraînements.
     */
    public function trainings()
    {
        return $this->hasMany(Training::class);
    }
}

