<?php

namespace App\Models;

use App\Enums\Nationality;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Nation du catalogue (mode Coupe du Monde), équivalent de Team pour les clubs.
 *
 * Domaine : tournoi de sélections. Le nom est la valeur de App\Enums\Nationality, identique à
 * `players.nationality` : l'effectif d'une sélection est donc dérivé des joueurs de cette nationalité
 * (NationalTeamAssembler) et non de contrats. Copiée par partie dans `game_teams` (base_nation_id).
 *
 * @property int $id
 * @property string $name
 * @property string $code
 * @property string|null $flag_path
 * @property string $tactical_style
 * @property string $management_philosophy
 * @property string|null $default_formation
 * @property bool $is_active
 */
class Nation extends Model
{
    protected $fillable = [
        'name', 'code', 'flag_path', 'tactical_style', 'management_philosophy', 'default_formation', 'is_active',
    ];

    protected $casts = ['is_active' => 'boolean'];

    /** Nations proposées au tournoi (les désactivées restent en base mais ne sont plus tirées). */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Noms des nations (ordre du catalogue), pour valider et lister les nationalités.
     *
     * @param  bool  $activeOnly  true : seulement les nations proposées au tournoi
     * @return array<int, string>
     */
    public static function names(bool $activeOnly = false): array
    {
        return static::query()->when($activeOnly, fn (Builder $q) => $q->where('is_active', true))->orderBy('id')->pluck('name')->all();
    }

    /**
     * URL du drapeau image de chaque nation qui en a un (nom => URL). Une seule requête :
     * à appeler une fois par réponse puis à réutiliser.
     *
     * @return array<string, string>
     */
    public static function flagUrls(): array
    {
        return static::query()->whereNotNull('flag_path')->pluck('flag_path', 'name')
            ->map(fn (string $path) => asset('storage/'.$path))->all();
    }

    /** Dossier public des drapeaux (versionné avec le code : le disque de production est éphémère). */
    public const FLAGS_DIR = 'nations';

    /** Extensions de drapeau reconnues, par ordre de préférence. */
    public const FLAG_EXTENSIONS = ['svg', 'webp', 'png'];

    /**
     * Renseigne `flag_path` des nations dont le fichier `public/storage/nations/{code}.{ext}` existe (flag_path relatif à /storage, comme les logos d'équipes)
     * (code ISO en minuscules). Idempotent ; ne touche pas aux nations sans fichier.
     *
     * @return int nombre de nations reliées à une image
     */
    public static function linkFlagsFromDisk(): int
    {
        $linked = 0;
        foreach (static::all() as $nation) {
            foreach (self::FLAG_EXTENSIONS as $ext) {
                $path = self::FLAGS_DIR.'/'.strtolower($nation->code).'.'.$ext;
                if (is_file(public_path('storage/'.$path))) {
                    $nation->update(['flag_path' => $path]);
                    $linked++;
                    break;
                }
            }
        }

        return $linked;
    }

    /** URL publique du drapeau image, ou null s'il n'y en a pas (repli sur l'emoji). */
    public function getFlagUrlAttribute(): ?string
    {
        return $this->flag_path ? asset('storage/'.$this->flag_path) : null;
    }

    /** Drapeau emoji de la nation (repli quand il n'y a pas d'image). */
    public function getFlagEmojiAttribute(): string
    {
        return Nationality::flag($this->name);
    }
}
