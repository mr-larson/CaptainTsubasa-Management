<?php

namespace Tests\Concerns;

use App\Models\BonusCard;
use App\Models\GameSaves\GameBonusCard;
use App\Models\GameSaves\GameContract;
use App\Models\GameSaves\GameMatch;
use App\Models\GameSaves\GamePlayer;
use App\Models\GameSaves\GameSave;
use App\Models\GameSaves\GameTeam;
use App\Models\User;

/**
 * Fabriques partagées pour monter un « monde de jeu » minimal dans les tests
 * fonctionnels des services métier (saison, match, tournoi, entraînement,
 * transferts). Évite la duplication des helpers ad hoc (cf. l'ancien
 * HotSeatWeekGatingTest) et garantit des enregistrements valides au regard du
 * schéma (colonnes par défaut, contrats actifs, titulaires).
 */
trait BuildsGameWorld
{
    private int $worldTeamSeq   = 0;
    private int $worldPlayerSeq = 0;

    protected function makeSave(array $attrs = []): GameSave
    {
        return GameSave::create(array_merge([
            'user_id'   => User::factory()->create()->id,
            'team_id'   => null,
            'period'    => 'college',
            'season'    => 1,
            'week'      => 1,
            'phase'     => 'season',
            'game_mode' => 'prebuilt',
            'state'     => null,
        ], $attrs));
    }

    protected function makeTeam(GameSave $save, array $attrs = []): GameTeam
    {
        $this->worldTeamSeq++;

        return GameTeam::create(array_merge([
            'game_save_id'  => $save->id,
            'base_team_id'  => null,
            'is_controlled' => false,
            'human_seat'    => null,
            'name'          => 'Équipe '.$this->worldTeamSeq,
            'budget'        => 100000,
            'wins'          => 0,
            'draws'         => 0,
            'losses'        => 0,
            'goals_for'     => 0,
            'goals_against' => 0,
        ], $attrs));
    }

    protected function makePlayer(GameSave $save, array $attrs = []): GamePlayer
    {
        $this->worldPlayerSeq++;

        return GamePlayer::create(array_merge([
            'game_save_id' => $save->id,
            'firstname'    => 'Prenom'.$this->worldPlayerSeq,
            'lastname'     => 'Nom'.$this->worldPlayerSeq,
            'position'     => 'MID',
            'speed'        => 50,
            'stamina'      => 100,
            'attack'       => 50,
            'defense'      => 50,
            'shot'         => 50,
            'pass'         => 50,
            'dribble'      => 50,
            'block'        => 50,
            'intercept'    => 50,
            'tackle'       => 50,
            'hand_save'    => 0,
            'punch_save'   => 0,
            'cost'         => 1000,
            'morale'       => 60, // bande neutre : xpFactor = 1.0
        ], $attrs));
    }

    protected function makeContract(GameSave $save, GameTeam $team, GamePlayer $player, array $attrs = []): GameContract
    {
        return GameContract::create(array_merge([
            'game_save_id'   => $save->id,
            'game_team_id'   => $team->id,
            'game_player_id' => $player->id,
            'salary'         => 1000,
            'start_week'     => 1,
            'end_week'       => null,
            'is_starter'     => true,
        ], $attrs));
    }

    protected function makeMatch(GameSave $save, GameTeam $home, GameTeam $away, array $attrs = []): GameMatch
    {
        return GameMatch::create(array_merge([
            'game_save_id' => $save->id,
            'week'         => 1,
            'home_team_id' => $home->id,
            'away_team_id' => $away->id,
            'status'       => 'scheduled',
        ], $attrs));
    }

    /**
     * Crée une équipe avec un effectif complet sous contrat actif.
     * Les 11 premiers joueurs sont titulaires (is_starter), le reste remplaçant.
     *
     * @return array{0: GameTeam, 1: \Illuminate\Support\Collection<int, GamePlayer>}
     */
    protected function makeTeamWithSquad(GameSave $save, array $teamAttrs = [], int $size = 11): array
    {
        $team    = $this->makeTeam($save, $teamAttrs);
        $players = collect();

        // Une formation plausible : 1 GK, 4 DEF, 3 MID, 3 ATT, puis remplaçants MID.
        $positions = ['GK', 'DEF', 'DEF', 'DEF', 'DEF', 'MID', 'MID', 'MID', 'ATT', 'ATT', 'ATT'];

        for ($k = 0; $k < $size; $k++) {
            $player = $this->makePlayer($save, ['position' => $positions[$k] ?? 'MID']);
            $this->makeContract($save, $team, $player, ['is_starter' => $k < 11]);
            $players->push($player);
        }

        return [$team, $players];
    }

    /** Définition de carte (catalogue bonus_cards). */
    protected function makeBonusCard(array $attrs = []): BonusCard
    {
        return BonusCard::create(array_merge([
            'name'            => 'Carte test',
            'description'     => 'Description de test.',
            'kind'            => 'bonus',
            'tier'            => 'bronze',
            'target'          => 'self',
            'execution_phase' => 'immediate',
            'effect_type'     => 'stamina_boost',
            'effect_value'    => ['amount' => 10],
            'cost'            => 1000,
            'base_weight'     => 100,
            'icon'            => '🃏',
        ], $attrs));
    }

    /** Instance possédée d'une carte par une équipe (game_bonus_cards). */
    protected function makeGameCard(GameSave $save, GameTeam $team, BonusCard $card, array $attrs = []): GameBonusCard
    {
        return GameBonusCard::create(array_merge([
            'game_save_id'     => $save->id,
            'bonus_card_id'    => $card->id,
            'game_team_id'     => $team->id,
            'tier'             => $card->tier,
            'cost_paid'        => $card->cost,
            'status'           => 'available',
            'purchased_season' => 1,
            'purchased_week'   => 1,
        ], $attrs));
    }
}
