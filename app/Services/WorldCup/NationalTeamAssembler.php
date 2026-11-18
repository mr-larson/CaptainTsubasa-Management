<?php

namespace App\Services\WorldCup;

use App\Contracts\Randomizer;
use App\Enums\Nationality;
use App\Services\Ai\AiLineUpService;
use Illuminate\Validation\ValidationException;
use App\Models\GameSaves\GameContract;
use App\Support\GameConfig;
use App\Models\GameSaves\GamePlayer;
use App\Models\GameSaves\GameSave;
use App\Models\GameSaves\GameTeam;
use App\Models\Nation;
use App\Models\Player;
use Illuminate\Support\Collection;
use App\Services\Players\MoraleService;

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

    public const SQUAD_SIZE = 18;
    public const MIN_PLAYERS = 11;
    public const MAX_PLAYERS = 20;

    public function __construct(private Randomizer $random, private AiLineUpService $lineups)
    {
    }

    public function preview(string $nation): ?array
    {
        $players = Player::where('nationality', $nation)->orderBy('lastname')->orderBy('firstname')->orderBy('id')->get();
        $squad = $this->automaticSquad($players);
        if ($squad->count() < self::MIN_PLAYERS || !$squad->contains(fn (Player $player) => $this->positionGroup($player) === 'GK')) {
            return null;
        }
        $lineup = $this->lineups->recommendInitialLineup($squad);

        return [
            'name' => $nation,
            'flag' => Nationality::flag($nation),
            'players' => $players->map(fn (Player $player) => [
                'id' => $player->id,
                'name' => $player->full_name,
                'position' => $player->position->value,
                'overall' => round($this->overall($player)),
            ])->all(),
            'selectedIds' => $squad->pluck('id')->all(),
            'formation' => $lineup['formation'],
            'description' => $this->describe($squad),
            'minPlayers' => self::MIN_PLAYERS,
            'maxPlayers' => self::MAX_PLAYERS,
        ];
    }

    /**
     * Monte toutes les sélections de la partie.
     *
     * Hot-seat : la nation $controlledNation prend le siège 1 (et la main au démarrage), les
     * $coHumanNations les sièges suivants, dans l'ordre. Seule la nation du siège 1 a une
     * convocation choisie ($playerIds) ; les autres managers humains reçoivent le meilleur
     * onze automatique, qu'ils ajustent ensuite dans « Mon équipe ».
     *
     * @param array<int, string> $nations          toutes les nations du tournoi (humaines comprises)
     * @param array<int, string> $coHumanNations   nations humaines supplémentaires (sièges 2, 3…)
     */
    public function assemble(GameSave $gameSave, array $nations, string $controlledNation, ?array $playerIds = null, array $coHumanNations = []): void
    {
        $state = $gameSave->gameState();
        $seats = array_flip(array_values(array_merge([$controlledNation], $coHumanNations))); // nation → siège - 1

        foreach ($nations as $nation) {
            $isHuman      = isset($seats[$nation]);
            $isControlled = $nation === $controlledNation;
            $query = Player::where('nationality', $nation)->orderBy('id');
            if ($isControlled && $playerIds !== null) {
                $squad = $query->whereKey($playerIds)->lockForUpdate()->get();
                if ($squad->count() !== count($playerIds)
                    || $squad->count() < self::MIN_PLAYERS
                    || $squad->count() > self::MAX_PLAYERS
                    || !$squad->contains(fn (Player $player) => $this->positionGroup($player) === 'GK')) {
                    throw ValidationException::withMessages([
                        'player_ids' => 'Convoque entre 11 et 20 joueurs disponibles de cette nation, dont au moins un gardien.',
                    ]);
                }
            } else {
                $squad = $this->automaticSquad($query->get());
                if ($squad->count() < self::MIN_PLAYERS || !$squad->contains(fn (Player $player) => $this->positionGroup($player) === 'GK')) {
                    if ($isHuman) {
                        throw ValidationException::withMessages(['nation' => "La sélection {$nation} n'a plus assez de joueurs pour concourir."]);
                    }
                    continue;
                }
            }

            $lineup = $this->lineups->recommendInitialLineup($squad);
            $catalog = Nation::where('name', $nation)->first();
            $gameTeam = GameTeam::create([
                'game_save_id'          => $gameSave->id,
                'base_team_id'          => null,
                'base_nation_id'        => $catalog?->id,
                'name'                  => $nation,
                'budget'                => 0,
                'wins'                  => 0,
                'draws'                 => 0,
                'losses'                => 0,
                'goals_for'             => 0,
                'goals_against'         => 0,
                'logo_path'             => $catalog?->flag_path,
                'formation'             => $lineup['formation'],
                'description'           => $this->describe($squad),
                'tactical_style'        => $catalog?->tactical_style ?? 'balanced',
                'management_philosophy' => $catalog?->management_philosophy ?? 'collective',
                'is_controlled'         => $isHuman,
                'human_seat'            => $isHuman ? $seats[$nation] + 1 : null,
            ]);

            $slots = $this->snapshotSquad($gameSave, $gameTeam, $squad, $lineup['slots']);
            $state->setLineup($gameTeam->id, ['formation' => $lineup['formation'], 'slots' => $slots]);

            if ($isControlled) {
                $gameSave->controlled_game_team_id = $gameTeam->id;
            }
        }

        $gameSave->save();
    }

    private function automaticSquad(Collection $players): Collection
    {
        $ranked = $players->sortByDesc(fn (Player $player) => $this->overall($player));
        $keepers = $ranked->filter(fn (Player $player) => $this->positionGroup($player) === 'GK');
        $field = $ranked->diff($keepers);

        return $keepers->take(1)->concat($field->take(10))
            ->concat($keepers->skip(1))->concat($field->skip(10))
            ->take(self::SQUAD_SIZE)->values();
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

        return "La sélection réunit ".implode(', ', array_slice($breakdown, 0, -1)).' et '.end($breakdown).'.';
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
                'game_save_id'                    => $gameSave->id,
                'game_team_id'                    => $team->id,
                'game_player_id'                  => $gamePlayer->id,
                'salary'                          => $player->cost ?? 0,
                'start_week'                      => 1,
                'end_week'                        => null, // actif toute la durée du tournoi
                'is_starter'                      => $slot !== null,
                'is_captain'                      => $player->id === $captainId,
                'captain_rerolls_remaining'       => 3,
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

        return GamePlayer::create([
            'game_save_id'        => $gameSave->id,
            'base_player_id'      => $player->id,
            'firstname'           => $player->firstname,
            'lastname'            => $player->lastname,
            'position'            => $player->position,
            'origin'              => $player->origin,
            'nationality'         => $player->nationality,
            'secondary_positions' => $player->secondary_positions ?? [],
            'description'         => $player->description,
            'photo_path'          => $player->photo_path,
            'speed'               => $gameSave->config()->initialSpeed((int) ($player->speed ?? $s['speed'] ?? 50)),
            'stamina'             => $gameSave->config()->initialStamina(),
            'luck'                => $gameSave->config()->rollLuck(),
            'attack'              => $player->attack     ?? $s['attack']     ?? 50,
            'defense'             => $player->defense    ?? $s['defense']    ?? 50,
            'shot'                => $player->shot       ?? $s['shot']       ?? 50,
            'pass'                => $player->pass       ?? $s['pass']       ?? 50,
            'dribble'             => $player->dribble    ?? $s['dribble']    ?? 50,
            'block'               => $player->block      ?? $s['block']      ?? 50,
            'intercept'           => $player->intercept  ?? $s['intercept']  ?? 50,
            'tackle'              => $player->tackle     ?? $s['tackle']     ?? 50,
            'heading'             => $player->headingStat(),
            'hand_save'           => $player->hand_save  ?? $s['hand_save']  ?? 0,
            'punch_save'          => $player->punch_save ?? $s['punch_save'] ?? 0,
            'special_moves'       => $player->special_moves ?? [],
            'cost'                => $player->cost ?? 0,
            'morale'              => $gameSave->getConfig('initial_morale_random')
                ? $this->random->int(GameConfig::INITIAL_MORALE_MIN, GameConfig::INITIAL_MORALE_MAX)
                : MoraleService::NEUTRAL_MORALE,
        ]);
    }

    /** Note synthétique d'un joueur selon son poste, pour le tri de sélection. */
    private function overall(Player $player): float
    {
        $s   = $player->stats ?? [];
        $get = fn(string $k): float => (float) ($s[$k] ?? $player->{$k} ?? 0);

        return match ($this->positionGroup($player)) {
            'GK'    => ($get('hand_save') + $get('punch_save') + $get('defense')) / 3,
            'DEF'   => ($get('defense') + $get('tackle') + $get('block') + $get('intercept')) / 4,
            'MID'   => ($get('pass') + $get('dribble') + $get('attack') + $get('speed')) / 4,
            default => ($get('shot') + $get('attack') + $get('dribble') + $get('speed')) / 4,
        };
    }

    /** Regroupe le poste du joueur en GK / DEF / MID / FW (MID par défaut). */
    private function positionGroup(Player $player): string
    {
        $position = $player->position;
        $value    = $position instanceof \BackedEnum ? $position->value : (string) $position;

        return match ($value) {
            'Goalkeeper' => 'GK',
            'Defender'   => 'DEF',
            'Forward'    => 'FW',
            default      => 'MID',
        };
    }
}
