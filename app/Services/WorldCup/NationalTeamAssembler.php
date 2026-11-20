<?php

namespace App\Services\WorldCup;

use App\Contracts\Randomizer;
use App\Helpers\InitialPlayerStats;
use App\Enums\Nationality;
use App\Models\GameSaves\GameContract;
use App\Models\GameSaves\GamePlayer;
use App\Models\GameSaves\GameSave;
use App\Models\GameSaves\GameTeam;
use App\Models\Nation;
use App\Models\Player;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

/**
 * Monte les sélections nationales d'une partie Coupe du Monde : pour chaque
 * nation participante, crée la GameTeam, snapshot ses meilleurs joueurs
 * (GamePlayer) et leurs contrats (GameContract), et désigne l'équipe contrôlée.
 *
 * Les effectifs sont constitués depuis le pool `players` filtré par
 * `nationality` (cf. App\Enums\Nationality). Réutilise la même logique de
 * snapshot que la création de partie classique (GameSaveController::start).
 */
class NationalTeamAssembler
{
    /**
     * Budget de départ de CHAQUE sélection : il finance entraînements, cartes bonus et demandes des joueurs (pas
     * de transferts ni de salaires en Coupe du Monde). Mesuré sur 4 tournois (8 et 16 équipes) : l'IA reçoit
     * environ 2 500 de revenus et en dépense 2 400 à 2 700, soit presque tout ce qu'elle a (1 500 + revenus) ;
     * un manager qui ne dépense rien finit vers 4 000-4 500. Départ et revenus étant identiques pour tous,
     * l'écart ne vient que de l'usage du budget : pas de rééquilibrage nécessaire.
     */
    public const START_BUDGET = 1500;

    public const SQUAD_SIZE = NationalSquadPicker::SQUAD_SIZE;

    public const MIN_PLAYERS = NationalSquadPicker::MIN_PLAYERS;

    public const MAX_PLAYERS = 20;

    public function __construct(private Randomizer $random, private NationalSquadPicker $picker) {}

    public function preview(string $nation, ?array $gameConfig = null, ?int $seed = null): ?array
    {
        $players = Player::nationalEligible()->where('nationality', $nation)->orderBy('lastname')->orderBy('firstname')->orderBy('id')->get();
        $catalog = Nation::where('name', $nation)->first();
        $picked = $this->picker->pick($players, $catalog?->default_formation, $catalog?->tactical_style ?? 'balanced', $catalog?->management_philosophy ?? 'collective');
        if ($picked === null) {
            return null;
        }
        [$squad, $lineup] = $picked;

        $previewSave = new GameSave(['state' => ['config' => $gameConfig ?? []]]);

        return [
            'name' => $nation,
            'flag' => Nationality::flag($nation),
            'flag_url' => $catalog?->flag_url,
            'players' => $players->map(function (Player $player) use ($previewSave, $seed) {
                $initial = InitialPlayerStats::forPlayer($previewSave, $seed ?? 0, $player->id, (int) ($player->stats['speed'] ?? 50));

                return [
                    'id' => $player->id,
                    'name' => $player->full_name,
                    'firstname' => $player->firstname,
                    'lastname' => $player->lastname,
                    'age' => $player->age,
                    'photo_path' => $player->photo_path,
                    'nationality' => $player->nationality,
                    'position' => $player->position->value,
                    'secondary_positions' => $player->secondary_positions ?? [],
                    'special_moves' => $player->special_moves ?? [],
                    'stats' => array_merge(Player::DEFAULT_STATS, $player->stats ?? [], $initial),
                    ...$initial,
                    'overall' => round($this->overall($player)),
                ];
            })->all(),
            'selectedIds' => $squad->pluck('id')->all(),
            'formation' => $lineup['formation'],
            'description' => $this->describe($squad),
            'minPlayers' => self::MIN_PLAYERS,
            'maxPlayers' => self::MAX_PLAYERS,
        ];
    }

    /**
     * Onze que l'IA alignerait avec cette convocation (même logique que la création de partie) : alimente le
     * terrain de la page de convocation. Null si les joueurs ne permettent pas d'aligner un onze.
     *
     * @param  array<int, int>  $playerIds  ids des joueurs convoqués
     * @return array{formation: string, slots: array<int, int>}|null  poste (1..11) => id de joueur
     */
    public function lineupPreview(string $nation, array $playerIds, ?string $formation = null): ?array
    {
        $squad = Player::nationalEligible()->where('nationality', $nation)->whereKey($playerIds)->orderBy('id')->get();
        if ($squad->count() < self::MIN_PLAYERS) {
            return null;
        }
        $lineup = $this->picker->lineupFor($squad, $formation ?? Nation::where('name', $nation)->first()?->default_formation, $formation !== null);

        return count($lineup['slots']) >= self::MIN_PLAYERS ? $lineup : null;
    }

    /**
     * Monte toutes les sélections de la partie.
     *
     * Hot-seat : la nation $controlledNation prend le siège 1 (et la main au démarrage), les
     * $coHumanNations les sièges suivants, dans l'ordre. Chaque manager humain peut avoir sa
     * convocation choisie ($playerIds pour le siège 1, $coPlayerIds[nation] pour les autres) ; sans
     * choix, il reçoit la convocation automatique de l'IA, qu'il ajuste ensuite dans « Mon équipe ».
     *
     * @param  array<int, string>  $nations  toutes les nations du tournoi (humaines comprises)
     * @param  array<int, string>  $coHumanNations  nations humaines supplémentaires (sièges 2, 3…)
     * @param  array<string, array<int, int>>  $coPlayerIds  convocation choisie par nation des co-managers
     */
    public function assemble(GameSave $gameSave, array $nations, string $controlledNation, ?array $playerIds = null, array $coHumanNations = [], array $coPlayerIds = [], ?string $formation = null, array $coFormations = [], ?array $lineupSlots = null, array $coLineupSlots = []): void
    {
        $state = $gameSave->gameState();
        $seats = array_flip(array_values(array_merge([$controlledNation], $coHumanNations))); // nation → siège - 1

        foreach ($nations as $nation) {
            $isHuman = isset($seats[$nation]);
            $isControlled = $nation === $controlledNation;
            $query = Player::nationalEligible()->where('nationality', $nation)->orderBy('id');
            $catalog = Nation::where('name', $nation)->first();
            $chosenIds = $isControlled ? $playerIds : ($isHuman ? ($coPlayerIds[$nation] ?? null) : null);
            if ($chosenIds !== null) {
                $squad = $query->whereKey($chosenIds)->lockForUpdate()->get();
                if ($squad->count() !== count($chosenIds)
                    || $squad->count() < self::MIN_PLAYERS
                    || $squad->count() > self::MAX_PLAYERS
                    || ! $squad->contains(fn (Player $player) => $this->positionGroup($player) === 'GK')) {
                    throw ValidationException::withMessages([
                        $isControlled ? 'player_ids' : 'co_player_ids' => "Convoque entre 11 et 20 joueurs disponibles de {$nation}, dont au moins un gardien.",
                    ]);
                }
                $chosenFormation = $isControlled ? $formation : ($coFormations[$nation] ?? null);
                $lineup = $this->picker->lineupFor($squad, $chosenFormation ?? $catalog?->default_formation, $chosenFormation !== null);
            } else {
                $picked = $this->picker->pick($query->get(), $catalog?->default_formation, $catalog?->tactical_style ?? 'balanced', $catalog?->management_philosophy ?? 'collective');
                if ($picked === null) {
                    if ($isHuman) {
                        throw ValidationException::withMessages(['nation' => "La sélection {$nation} n'a plus assez de joueurs pour concourir."]);
                    }

                    continue;
                }
                [$squad, $lineup] = $picked;
            }

            $chosenSlots = $isControlled ? $lineupSlots : ($coLineupSlots[$nation] ?? null);
            if ($isHuman && $chosenSlots !== null) {
                $lineup['slots'] = $this->validateChosenSlots($squad, $chosenSlots, $isControlled ? 'lineup_slots' : 'co_lineup_slots');
            }

            $gameTeam = GameTeam::create([
                'game_save_id' => $gameSave->id,
                'base_team_id' => null,
                'base_nation_id' => $catalog?->id,
                'name' => $nation,
                'budget' => self::START_BUDGET,
                'wins' => 0,
                'draws' => 0,
                'losses' => 0,
                'goals_for' => 0,
                'goals_against' => 0,
                'logo_path' => $catalog?->flag_path,
                'formation' => $lineup['formation'],
                'description' => $this->describe($squad),
                'tactical_style' => $catalog?->tactical_style ?? 'balanced',
                'management_philosophy' => $catalog?->management_philosophy ?? 'collective',
                'is_controlled' => $isHuman,
                'human_seat' => $isHuman ? $seats[$nation] + 1 : null,
            ]);

            $slots = $this->snapshotSquad($gameSave, $gameTeam, $squad, $lineup['slots']);
            $state->setLineup($gameTeam->id, ['formation' => $lineup['formation'], 'slots' => $slots]);

            if ($isControlled) {
                $gameSave->controlled_game_team_id = $gameTeam->id;
            }
        }

        $gameSave->save();
    }

    /** Seuls onze joueurs distincts de la convocation peuvent figurer sur les onze postes. */
    private function validateChosenSlots(Collection $squad, array $slots, string $field): array
    {
        $keys = array_map('intval', array_keys($slots));
        sort($keys);
        $ids = array_map('intval', array_values($slots));
        if ($keys !== range(1, 11) || count(array_unique($ids)) !== 11 || array_diff($ids, $squad->pluck('id')->all()) !== []) {
            throw ValidationException::withMessages([$field => 'Place onze joueurs convoqués différents sur les onze postes.']);
        }
        $normalized = [];
        foreach ($slots as $slot => $id) $normalized[(int) $slot] = (int) $id;
        ksort($normalized);
        return $normalized;
    }

    private function describe(Collection $squad): string
    {
        $groups = $squad->countBy(fn (Player $player) => $this->positionGroup($player));
        $roles = ['GK' => ['gardien', 'gardiens'], 'DEF' => ['défenseur', 'défenseurs'], 'MID' => ['milieu', 'milieux'], 'FW' => ['attaquant', 'attaquants']];
        $breakdown = [];
        foreach ($roles as $group => [$singular, $plural]) {
            $count = $groups->get($group, 0);
            $breakdown[] = $count.' '.($count > 1 ? $plural : $singular);
        }

        return 'La sélection réunit '.implode(', ', array_slice($breakdown, 0, -1)).' et '.end($breakdown).'.';
    }

    /** Copie les joueurs et convertit les emplacements du onze en identifiants de la partie. */
    private function snapshotSquad(GameSave $gameSave, GameTeam $team, Collection $squad, array $slots): array
    {
        $starterSlots = array_flip($slots);
        $captainId = $squad->filter(fn (Player $player) => isset($starterSlots[$player->id]) && $this->positionGroup($player) !== 'GK')
            ->sortByDesc(fn (Player $player) => $this->overall($player))->first()?->id ?? $slots[1];
        $subNumber = self::MIN_PLAYERS + 1;
        $savedSlots = [];

        foreach ($squad as $player) {
            $slot = $starterSlots[$player->id] ?? null;
            $gamePlayer = $this->snapshotPlayer($gameSave, $player);
            $gamePlayer->number = $slot ?? $subNumber++;
            $gamePlayer->save();

            if ($slot !== null) {
                $savedSlots[$slot] = $gamePlayer->id;
            }

            GameContract::create([
                'game_save_id' => $gameSave->id,
                'game_team_id' => $team->id,
                'game_player_id' => $gamePlayer->id,
                'salary' => $player->cost ?? 0,
                'start_week' => 1,
                'end_week' => null, // actif toute la durée du tournoi
                'is_starter' => $slot !== null,
                'is_captain' => $player->id === $captainId,
                'captain_rerolls_remaining' => 3,
                'captain_reroll_used_this_action' => false,
            ]);
        }

        ksort($savedSlots);

        return $savedSlots;
    }

    /** Snapshot d'un joueur de base vers game_players (miroir de GameSaveController::start). */
    private function snapshotPlayer(GameSave $gameSave, Player $player): GamePlayer
    {
        $s = $player->stats ?? [];
        $initial = InitialPlayerStats::forPlayer($gameSave, (int) ($gameSave->state['player_stats_seed'] ?? 0), $player->id, (int) ($player->speed ?? $s['speed'] ?? 50));

        return GamePlayer::create([
            'game_save_id' => $gameSave->id,
            'base_player_id' => $player->id,
            'firstname' => $player->firstname,
            'lastname' => $player->lastname,
            'position' => $player->position,
            'origin' => $player->origin,
            'nationality' => $player->nationality,
            'secondary_positions' => $player->secondary_positions ?? [],
            'description' => $player->description,
            'photo_path' => $player->photo_path,
            'speed' => $initial['speed'],
            'stamina' => $initial['stamina'],
            'luck' => $initial['luck'],
            'attack' => $player->attack ?? $s['attack'] ?? 50,
            'defense' => $player->defense ?? $s['defense'] ?? 50,
            'shot' => $player->shot ?? $s['shot'] ?? 50,
            'pass' => $player->pass ?? $s['pass'] ?? 50,
            'dribble' => $player->dribble ?? $s['dribble'] ?? 50,
            'block' => $player->block ?? $s['block'] ?? 50,
            'intercept' => $player->intercept ?? $s['intercept'] ?? 50,
            'tackle' => $player->tackle ?? $s['tackle'] ?? 50,
            'heading' => $player->headingStat(),
            'hand_save' => $player->hand_save ?? $s['hand_save'] ?? 0,
            'punch_save' => $player->punch_save ?? $s['punch_save'] ?? 0,
            'special_moves' => $player->special_moves ?? [],
            'cost' => $player->cost ?? 0,
            'morale' => $initial['morale'],
        ]);
    }

    /**
     * Note moyenne du onze de départ d'une sélection : moyenne des OVR (moyenne des douze stats renseignées, comme
     * l'affiche l'interface, tête exclue) des onze titulaires que l'IA aligne. Null si la nation ne peut pas aligner un onze.
     */
    public function startingElevenRating(string $nation): ?float
    {
        $players = Player::nationalEligible()->where('nationality', $nation)->orderBy('lastname')->orderBy('firstname')->orderBy('id')->get();
        $catalog = Nation::where('name', $nation)->first();
        $picked = $this->picker->pick($players, $catalog?->default_formation, $catalog?->tactical_style ?? 'balanced', $catalog?->management_philosophy ?? 'collective');
        if ($picked === null) {
            return null;
        }
        [$squad, $lineup] = $picked;
        $byId = $squad->keyBy('id');
        $starters = collect($lineup['slots'])->map(fn ($id) => $byId->get($id))->filter();
        if ($starters->isEmpty()) {
            return null;
        }

        return round($starters->avg(function (Player $player): float {
            $values = collect($player->stats ?? [])->only(['speed', 'stamina', 'attack', 'defense', 'shot', 'pass', 'dribble', 'block', 'intercept', 'tackle', 'hand_save', 'punch_save'])->filter(fn ($v) => is_numeric($v) && $v > 0);

            return $values->isEmpty() ? 0.0 : (float) $values->avg();
        }), 1);
    }

    /** Note synthétique d'un joueur selon son poste (définie dans NationalSquadPicker). */
    private function overall(Player $player): float
    {
        return $this->picker->overall($player);
    }

    /** Regroupe le poste du joueur en GK / DEF / MID / FW (MID par défaut). */
    private function positionGroup(Player $player): string
    {
        $position = $player->position;
        $value = $position instanceof \BackedEnum ? $position->value : (string) $position;

        return match ($value) {
            'Goalkeeper' => 'GK',
            'Defender' => 'DEF',
            'Forward' => 'FW',
            default => 'MID',
        };
    }
}
