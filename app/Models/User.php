<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * Compte utilisateur. Possède des parties (gameSaves) et éventuellement des packages
 * de période. Le droit d'administration est un drapeau à part (isAdmin).
 */
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_admin' => 'boolean',
        'pixel_art' => 'boolean',
    ];

    /**
     * Indique si l'utilisateur dispose des droits d'administration.
     *
     * Volontairement hors de $fillable : le flag ne doit jamais être
     * assignable en masse depuis une requête utilisateur.
     */
    public function isAdmin(): bool
    {
        return (bool) $this->is_admin;
    }

    /** Parties de l'utilisateur. */
    public function gameSaves()
    {
        return $this->hasMany(\App\Models\GameSaves\GameSave::class);
    }
}
