<?php

namespace App\Services;

use App\Models\GameSaves\GamePlayer;
use App\Models\GameSaves\GamePromise;
use App\Models\GameSaves\GameSave;
use App\Models\GameSaves\GameTeam;
use App\Models\GameSaves\GameInjury;
use App\Models\GameSaves\GamePositionTraining;
use App\Models\GameSaves\GameSanction;
use App\Models\PlayerLink;
use App\Services\BonusCardActivationService;
use App\Services\PlayerChemistryService;

/**
 * Met en forme les effectifs pour le moteur de match du front (écran de match).
 */
class MatchRosterPresenter
{
    /**
     * Équipe → liste de joueurs au format du moteur de match : 11 titulaires
     * (slots de la composition) puis remplaçants, avec stats modifiées par les
     * cartes, disponibilité, promesses, duos et capitaine.
     */
    public function forTeam(GameTeam $team, GameSave $gameSave, int $matchId): \Illuminate\Support\Collection
    {
        $currentWeek = $gameSave->week ?? 1;

        // Joueurs blessés ou suspendus cette semaine
        $injuredPlayerIds = GameInjury::where('game_save_id', $gameSave->id)
            ->where('week_return', '>', $currentWeek)
            ->pluck('game_player_id')
            ->toArray();

        $suspendedPlayerIds = GameSanction::where('game_save_id', $gameSave->id)
            ->where('week_return', '>', $currentWeek)
            ->where('weeks_suspended', '>', 0)
            ->pluck('game_player_id')
            ->toArray();

        // Malus « titulaire consigné » infligé à cette équipe (par le joueur ou l'IA)
        $benchedByMalus = app(BonusCardActivationService::class)
            ->getBenchedPlayerIds($gameSave, $team->id);

        $unavailableIds = array_unique(array_merge(
            $injuredPlayerIds, $suspendedPlayerIds, $benchedByMalus,
            GamePositionTraining::unavailablePlayerIds($gameSave->id, $currentWeek),
        ));

        // Modificateurs de stats "ce match" : boosts propres + debuffs reçus
        $statMods = app(BonusCardActivationService::class)
            ->getMatchStatModifiers($gameSave, $team->id, $matchId);

        $contracts = $team->contracts->loadMissing('gamePlayer');
        $teamLineup = $gameSave->gameState()->lineupSlots($team->id);
        $promises = GamePromise::where('game_save_id', $gameSave->id)
            ->where('game_team_id', $team->id)
            ->where('season', $gameSave->season)
            ->where('status', 'pending')
            ->where('start_week', '<=', $currentWeek)
            ->where('due_week', '>=', $currentWeek)
            ->get(['id', 'game_player_id', 'type', 'status', 'start_week', 'due_week',
                'target_matches', 'played_matches', 'target_turns'])
            ->groupBy('game_player_id');


        // Duos d'alchimie : partenaires « une-deux » présents dans la même équipe
        // (résolus via base_player_id → player_links).
        $duoPartners = $this->duoPartnersFor($gameSave, $contracts);

        $starters = $contracts->sortByDesc('is_starter')->unique('game_player_id')->values();
        $ordered   = collect();

        if ($teamLineup) {
            for ($slot = 1; $slot <= 11; $slot++) {
                $pid = $teamLineup[$slot] ?? null;
                if ($pid) {
                    $c = $starters->where('is_starter', true)->firstWhere('game_player_id', $pid);
                    if ($c) {
                        $ordered->push([$slot, $c]);
                        $starters = $starters->reject(fn($cc) => $cc->id === $c->id)->values();
                        continue;
                    }
                }
                $ordered->push([$slot, null]);
            }

            // Comble les slots non résolus (lineup obsolète / IDs périmés) avec
            // les titulaires restants, pour ne jamais afficher de slot vide
            // tant qu'il reste des titulaires disponibles.
            $ordered = $ordered->map(function (array $row) use (&$starters) {
                [$slot, $c] = $row;
                if ($c === null && $starters->isNotEmpty()) {
                    $c = $starters->shift();
                }
                return [$slot, $c];
            });
        } else {
            for ($slot = 1; $slot <= 11; $slot++) {
                $ordered->push([$slot, $starters[$slot - 1] ?? null]);
            }
        }

        $starters11 = $ordered->map(function (array $row) use ($unavailableIds, $statMods, $duoPartners, $promises) {
            [$slot, $c] = $row;
            if (!$c || !$c->gamePlayer) {
                return [
                    'id' => null, 'number' => $slot,
                    'firstname' => '', 'lastname' => '', 'position' => '',
                    // Slot vide (effectif incomplet) : reste un titulaire pour que
                    // le front ne décale pas les slots suivants (gardien en slot 1).
                    'is_starter' => true, 'is_placeholder' => true,
                    'photo_path' => null, 'photo_url' => null,
                    'stats' => null, 'special_moves' => [], 'is_available' => true,
                    'yellow_cards' => 0,
                    'duo_partners' => [],
                    'is_captain'                => $c?->is_captain ?? false,
                    'contract_id'               => $c?->id,
                    'captain_rerolls_remaining' => $c?->captain_rerolls_remaining ?? 3,
                    'captain_reroll_used_this_action' => false,
                ];
            }
            return $this->row($c, $slot, true, $unavailableIds, $statMods, $duoPartners, $promises);
        })->values();

        $startingPlayerIds = $starters11->pluck('id')->filter();
        $subContracts = $contracts
            ->filter(fn($c) => !$c->is_starter && $c->gamePlayer)
            ->whereNotIn('game_player_id', $startingPlayerIds)
            ->unique('game_player_id')
            ->values();
        $subs = $subContracts->map(fn ($c) => $this->row(
            $c, $c->gamePlayer->number ?? $c->gamePlayer->id, false, $unavailableIds, $statMods, $duoPartners, $promises,
        ))->values();

        return $starters11->concat($subs);
    }

    /** Ligne joueur commune aux titulaires et aux remplaçants. */
    private function row($c, int $number, bool $isStarter, array $unavailableIds, array $statMods, array $duoPartners, $promises): array
    {
        $p = $c->gamePlayer;

        return [
            'id'            => $p->id,
            'number'        => $number,
            'promises'      => $promises->get($p->id, collect())->values()->all(),
            'firstname'     => $p->firstname,
            'lastname'      => $p->lastname,
            'position'      => $p->position,
            'secondary_positions' => $p->secondary_positions ?? [],
            'is_starter'    => $isStarter,
            'photo_path'    => $p->photo_path,
            'photo_url'     => $p->photo_url,
            'stats'         => $this->playerStatsArray($p, $this->matchDeltaFor($statMods, $p->id)),
            'special_moves' => $p->special_moves ?? [],
            'is_available'  => !in_array($p->id, $unavailableIds),
            'yellow_cards'  => 0,
            'morale'        => (int) ($p->morale ?? 60),
            'duo_partners'  => $duoPartners[$p->id] ?? [],
            // ── Captain ──
            'is_captain'                      => $c->is_captain,
            'captain_rerolls_remaining'       => $c->captain_rerolls_remaining,
            'captain_reroll_used_this_action' => $c->captain_reroll_used_this_action,
        ];
    }

    /**
     * Partenaires de duo par game_player_id, restreints à l'effectif fourni
     * (le une-deux exige les deux joueurs dans la même équipe) :
     * [game_player_id => [['id' => game_player_id partenaire, 'label' => ?string], …]].
     */
    private function duoPartnersFor(GameSave $gameSave, \Illuminate\Support\Collection $contracts): array
    {
        $players = $contracts->map(fn ($c) => $c->gamePlayer)->filter();

        $gameIdsByBase = [];
        foreach ($players as $p) {
            if ($p->base_player_id) {
                $gameIdsByBase[$p->base_player_id][] = $p->id;
            }
        }
        $partnerMap = PlayerLink::partnerMapFor(array_keys($gameIdsByBase));
        $result = app(PlayerChemistryService::class)->confirmedPartners($gameSave, $players->pluck('id')->all());
        foreach ($players as $p) {
            foreach ($partnerMap[$p->base_player_id] ?? [] as $entry) {
                foreach ($gameIdsByBase[$entry['partner_id']] ?? [] as $partnerGameId) {
                    $result[$p->id][] = ['id' => $partnerGameId, 'label' => $entry['label']];
                }
            }
        }

        return $result;
    }

    /**
     * Construit le tableau de stats d'un joueur pour le moteur, en appliquant
     * les modificateurs "ce match" (boosts pre_match et debuffs subis),
     * bornés à [1, 100].
     */
    private function playerStatsArray(GamePlayer $p, array $deltas = []): array
    {
        $stats = [
            'speed' => $p->speed, 'stamina' => $p->stamina,
            'attack' => $p->attack, 'defense' => $p->defense,
            'shot' => $p->shot, 'pass' => $p->pass, 'dribble' => $p->dribble,
            'block' => $p->block, 'intercept' => $p->intercept, 'tackle' => $p->tackle,
            'heading' => $p->heading, 'luck' => $p->luck, 'hand_save' => $p->hand_save, 'punch_save' => $p->punch_save,
        ];

        foreach ($deltas as $stat => $amount) {
            if (array_key_exists($stat, $stats)) {
                $stats[$stat] = max(1, min(100, (int) $stats[$stat] + (int) $amount));
            }
        }

        return $stats;
    }

    /**
     * Fusionne le delta d'équipe et le delta spécifique d'un joueur (debuff de
     * cadre) en une seule carte de modificateurs pour ce joueur.
     */
    private function matchDeltaFor(array $mods, $playerId): array
    {
        $delta = $mods['team'] ?? [];
        foreach (($mods['players'][$playerId] ?? []) as $stat => $v) {
            $delta[$stat] = ($delta[$stat] ?? 0) + (int) $v;
        }
        return $delta;
    }
}
