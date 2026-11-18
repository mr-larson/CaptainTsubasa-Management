<?php

namespace App\Services\WorldCup;

use App\Contracts\Randomizer;
use App\Enums\GamePhase;
use App\Enums\Nationality;
use App\Models\GameSaves\GameSave;
use App\Models\Player;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use App\Services\Season\SeasonBootstrapService;

/**
 * Construit une nouvelle partie « COUPE DU MONDE » : un tournoi de sélections
 * nationales (poules puis élimination directe) dirigé par le manager d'une nation.
 *
 * Deux responsabilités :
 *   1. dire quelles nations sont JOUABLES (playableNations / isPlayable) ;
 *   2. construire la partie à partir de la nation choisie (build).
 *
 * Contrairement à la Ligue (NewGameBuilder), les équipes ne sont pas copiées d'une
 * table d'équipes : chaque sélection est MONTÉE à partir des joueurs de sa nationalité
 * par NationalTeamAssembler.
 */
class NewWorldCupBuilder
{
    /** Tailles de tournoi proposées (nombre de sélections) : 2 groupes de 4, ou 4 groupes de 4. */
    public const TOURNAMENT_SIZES = [8, 16];

    /** Taille par défaut d'un tournoi. */
    public const DEFAULT_SIZE = 8;

    /** Managers humains maximum autour de la même table (nation principale + co-managers). */
    public const MAX_HUMANS = 4;

    /** Effectif minimum pour aligner un onze : 11 joueurs dont au moins 1 gardien. */
    private const MIN_GOALKEEPERS = 1;

    /** Noms des nations jouables, calculés une seule fois par instance. */
    private ?Collection $playableNames = null;

    public function __construct(
        private Randomizer $random,
        private NationalTeamAssembler $assembler,
        private TournamentService $tournament,
        private SeasonBootstrapService $seasonBootstrap,
    ) {
    }

    // ──────────────────────────────────────────────────────────────
    //   NATIONS JOUABLES
    // ──────────────────────────────────────────────────────────────

    /**
     * Nations proposées pour la Coupe du Monde, d'après le pool de joueurs (colonne
     * « nationality »). Une nation est « jouable » si elle peut aligner un onze :
     * au moins 11 joueurs dont 1 gardien.
     *
     * Tri : nations jouables d'abord, puis par effectif décroissant.
     *
     * @return array<int, array<string, mixed>> une ligne par nation : name, flag, total, goalkeepers, playable
     */
    public function playableNations(): array
    {
        $rows = Player::query()
            ->whereNotNull('nationality')
            ->selectRaw('nationality, COUNT(*) as total')
            ->selectRaw("SUM(CASE WHEN position = 'Goalkeeper' THEN 1 ELSE 0 END) as goalkeepers")
            ->groupBy('nationality')
            ->get()
            ->keyBy('nationality');

        $nations = [];
        foreach (Nationality::ALL as $name) {
            $row   = $rows->get($name);
            $total = (int) ($row->total ?? 0);
            $gk    = (int) ($row->goalkeepers ?? 0);

            $nations[] = [
                'name'        => $name,
                'flag'        => Nationality::flag($name),
                'total'       => $total,
                'goalkeepers' => $gk,
                'playable'    => $total >= NationalTeamAssembler::MIN_PLAYERS && $gk >= self::MIN_GOALKEEPERS,
            ];
        }

        usort($nations, fn ($a, $b) => [$b['playable'], $b['total']] <=> [$a['playable'], $a['total']]);

        return $nations;
    }

    public function squadPreview(string $nation): ?array
    {
        return $this->assembler->preview($nation);
    }

    /** Nombre de nations jouables : borne haute de la taille de tournoi choisissable. */
    public function playableCount(): int
    {
        return $this->playableNames()->count();
    }

    /** Vrai si la nation a assez de joueurs (dont un gardien) pour disputer le tournoi. */
    public function isPlayable(string $nation): bool
    {
        return $this->playableNames()->contains($nation);
    }

    /** Noms des seules nations jouables (calculés une fois, réutilisés ensuite). */
    private function playableNames(): Collection
    {
        return $this->playableNames ??= collect($this->playableNations())
            ->where('playable', true)
            ->pluck('name');
    }

    // ──────────────────────────────────────────────────────────────
    //   CONSTRUCTION DE LA PARTIE
    // ──────────────────────────────────────────────────────────────

    /**
     * Crée la partie et la renvoie. Tout est fait dans une transaction : si une
     * étape échoue, aucune partie à moitié créée n'apparaît dans la liste.
     *
     * Déroulé :
     *   1. tirage au sort des adversaires parmi les autres nations jouables (8 ou 16 sélections) ;
     *   2. création de la sauvegarde (phase « world_cup », semaine 1) ;
     *   3. montage de chaque sélection (équipes, joueurs, contrats) ;
     *   4. tirage des poules et calendrier de la phase de groupes ;
     *   5. préparation de la première semaine.
     *
     * L'appelant doit avoir vérifié que la nation est jouable (isPlayable).
     *
     * @param array $data données validées : nation, period, label, game_config (déjà nettoyée), nations_count (8 ou 16, facultatif), co_nations (nations des autres managers hot-seat, facultatif)
     */
    public function build(User $user, array $data): GameSave
    {
        $controlled = $data['nation'];
        $size       = (int) ($data['nations_count'] ?? self::DEFAULT_SIZE);
        $coHumans   = array_values(array_unique(array_diff($data['co_nations'] ?? [], [$controlled])));
        $humans     = array_merge([$controlled], $coHumans);
        $nations    = array_merge($humans, $this->drawOpponents($humans, $size));
        $state      = ! empty($data['game_config']) ? ['config' => $data['game_config']] : null;

        return DB::transaction(function () use ($user, $data, $nations, $controlled, $coHumans, $state) {
            $gameSave = GameSave::create([
                'user_id'          => $user->id,
                'team_id'          => null,
                'period'           => $data['period'],
                'season'           => 1,
                'week'             => 1,
                'phase'            => GamePhase::WorldCup,
                'game_mode'        => 'prebuilt',
                'competition_type' => 'world_cup',
                'label'            => $data['label'] ?? null,
                'state'            => $state,
            ]);

            $this->assembler->assemble($gameSave, $nations, $controlled, $data['player_ids'] ?? null, $coHumans);
            $this->tournament->generateGroups($gameSave);                  // poules et calendrier
            $this->seasonBootstrap->prepare($gameSave);                    // première semaine

            return $gameSave;
        });
    }

    /**
     * Adversaires tirés au sort parmi les autres nations jouables, de quoi remplir le
     * tournoi (la nation du manager compte pour une des sélections).
     *
     * @param  array<int, string> $humans nations des managers humains (déjà qualifiées d'office)
     * @return array<int, string> noms de nations
     */
    private function drawOpponents(array $humans, int $size): array
    {
        $others = $this->playableNames()->reject(fn ($name) => in_array($name, $humans, true))->values()->all();

        return array_slice($this->random->shuffle($others), 0, $size - count($humans));
    }
}
