<?php

namespace App\Services;

use App\Models\GameSaves\GameContract;
use App\Models\GameSaves\GamePlayer;
use App\Models\GameSaves\GameSave;
use App\Models\GameSaves\GameTeam;
use App\Models\Player;
use Illuminate\Support\Collection;

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
    public const STARTERS   = 11;
    public const FORMATION  = '3-2-3-2';

    /**
     * @param array<int, string> $nations          noms de nations (Nationality::ALL)
     * @param string             $controlledNation  nation pilotée par le joueur
     */
    public function assemble(GameSave $gameSave, array $nations, string $controlledNation): void
    {
        foreach ($nations as $nation) {
            $squad = $this->selectSquad($nation);
            if ($squad->count() < self::STARTERS) {
                continue; // sécurité : nation non alignable, ignorée
            }

            $isControlled = $nation === $controlledNation;

            $gameTeam = GameTeam::create([
                'game_save_id'          => $gameSave->id,
                'base_team_id'          => null,
                'name'                  => $nation,
                'budget'                => 0,
                'wins'                  => 0,
                'draws'                 => 0,
                'losses'                => 0,
                'goals_for'             => 0,
                'goals_against'         => 0,
                'formation'             => self::FORMATION,
                'tactical_style'        => 'balanced',
                'management_philosophy' => 'collective',
                'is_controlled'         => $isControlled,
                'human_seat'            => $isControlled ? 1 : null,
            ]);

            $this->snapshotSquad($gameSave, $gameTeam, $squad);

            if ($isControlled) {
                $gameSave->controlled_game_team_id = $gameTeam->id;
            }
        }

        $gameSave->save();
    }

    /**
     * Sélectionne jusqu'à SQUAD_SIZE joueurs d'une nation, ORDONNÉS : le XI
     * d'abord (1 gardien + 10 meilleurs joueurs de champ), puis les remplaçants
     * (gardiens restants + reste des joueurs de champ par note décroissante).
     *
     * @return Collection<int, array{player: Player, starter: bool, captain: bool}>
     */
    private function selectSquad(string $nation): Collection
    {
        $entries = Player::where('nationality', $nation)->get()->map(fn(Player $p) => [
            'player'  => $p,
            'group'   => $this->positionGroup($p),
            'overall' => $this->overall($p),
        ]);

        $gks   = $entries->where('group', 'GK')->sortByDesc('overall')->values();
        $field = $entries->where('group', '!=', 'GK')->sortByDesc('overall')->values();

        // XI : 1 gardien (si présent) + meilleurs joueurs de champ.
        $starters = collect();
        if ($gks->isNotEmpty()) {
            $starters->push($gks->first());
        }
        $fieldStarters = $field->take(self::STARTERS - $starters->count());
        $starters      = $starters->concat($fieldStarters);

        // Remplaçants : gardiens restants + joueurs de champ restants.
        $subs = $gks->slice(1)->concat($field->slice($fieldStarters->count()));

        $ordered = $starters->concat($subs)->take(self::SQUAD_SIZE)->values();

        // Capitaine = meilleur joueur de champ titulaire (sinon, meilleur joueur).
        $captainPlayerId = ($field->first()['player'] ?? $ordered->first()['player'])->id;

        return $ordered->values()->map(fn($e, $i) => [
            'player'  => $e['player'],
            'starter' => $i < self::STARTERS,
            'captain' => $e['player']->id === $captainPlayerId,
        ]);
    }

    /**
     * @param Collection<int, array{player: Player, starter: bool, captain: bool}> $squad
     */
    private function snapshotSquad(GameSave $gameSave, GameTeam $team, Collection $squad): void
    {
        $starterNumber = 1;
        $subNumber     = 12;

        foreach ($squad as $entry) {
            $isStarter = $entry['starter'];
            $gamePlayer = $this->snapshotPlayer($gameSave, $entry['player']);
            $gamePlayer->number = $isStarter ? $starterNumber++ : $subNumber++;
            $gamePlayer->save();

            GameContract::create([
                'game_save_id'                    => $gameSave->id,
                'game_team_id'                    => $team->id,
                'game_player_id'                  => $gamePlayer->id,
                'salary'                          => $entry['player']->cost ?? 0,
                'start_week'                      => 1,
                'end_week'                        => null, // actif toute la durée du tournoi
                'is_starter'                      => $isStarter,
                'is_captain'                      => $entry['captain'],
                'captain_rerolls_remaining'       => 3,
                'captain_reroll_used_this_action' => false,
            ]);
        }
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
            'speed'               => $player->speed      ?? $s['speed']      ?? 50,
            'stamina'             => rand(60, 100),
            'attack'              => $player->attack     ?? $s['attack']     ?? 50,
            'defense'             => $player->defense    ?? $s['defense']    ?? 50,
            'shot'                => $player->shot       ?? $s['shot']       ?? 50,
            'pass'                => $player->pass       ?? $s['pass']       ?? 50,
            'dribble'             => $player->dribble    ?? $s['dribble']    ?? 50,
            'block'               => $player->block      ?? $s['block']      ?? 50,
            'intercept'           => $player->intercept  ?? $s['intercept']  ?? 50,
            'tackle'              => $player->tackle     ?? $s['tackle']     ?? 50,
            'heading'             => $player->heading    ?? $s['heading']    ?? 15,
            'hand_save'           => $player->hand_save  ?? $s['hand_save']  ?? 0,
            'punch_save'          => $player->punch_save ?? $s['punch_save'] ?? 0,
            'special_moves'       => $player->special_moves ?? [],
            'cost'                => $player->cost ?? 0,
            'morale'              => $gameSave->getConfig('initial_morale_random')
                ? rand(GameSave::INITIAL_MORALE_MIN, GameSave::INITIAL_MORALE_MAX)
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
