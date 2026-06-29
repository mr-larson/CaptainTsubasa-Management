<?php

namespace App\Services;

use App\Models\GameSaves\GameContract;
use App\Models\GameSaves\GamePlayer;
use App\Models\GameSaves\GameSave;
use App\Models\GameSaves\GameTeam;
use Illuminate\Support\Facades\DB;

class DraftService
{
    public const MIN_SQUAD   = 14;
    public const MAX_SQUAD   = 18;
    public const DRAFT_BONUS = 5000;

    public const DRAFT_DISCOUNT = 0.5; // Les joueurs coûtent moitié prix au draft

    /**
     * Initialise un état de draft pour l'ordre d'équipes donné :
     * applique le bonus budget de draft et crée draft state dans le state.
     * Utilisé pour la draft initiale et les drafts d'intersaison.
     */
    public function initDraft(GameSave $gameSave, array $teamIdsOrder): void
    {
        $teams = GameTeam::where('game_save_id', $gameSave->id)
            ->whereIn('id', $teamIdsOrder)
            ->get();

        foreach ($teams as $gameTeam) {
            $gameTeam->budget = ($gameTeam->budget ?? 0) + self::DRAFT_BONUS;
            $gameTeam->save();
        }

        $state = $gameSave->state ?? [];
        $state['draft'] = [
            'order'              => array_values($teamIdsOrder),
            'current_pick_index' => 0,
            'round'              => 1,
            'picks'              => [],
            'completed'          => false,
            'finished_teams'     => [],
        ];
        $gameSave->state = $state;
        $gameSave->save();
    }

    /**
     * Exécute un pick : assigne un joueur libre à l'équipe courante.
     * Retourne le pick enregistré ou null si invalide.
     */
    public function executePick(GameSave $gameSave, int $playerId): ?array
    {
        $state = $gameSave->state ?? [];
        $draft = $state['draft'] ?? null;

        if (!$draft || ($draft['completed'] ?? false)) return null;

        $order     = $draft['order'] ?? [];
        $pickIndex = $draft['current_pick_index'] ?? 0;
        $round     = $draft['round'] ?? 1;

        // Déterminer l'équipe courante (snake : rounds pairs = inversé)
        $effectiveOrder = ($round % 2 === 0) ? array_reverse($order) : $order;
        $currentTeamId  = $effectiveOrder[$pickIndex] ?? null;

        if (!$currentTeamId) return null;

        $team = GameTeam::where('game_save_id', $gameSave->id)->find($currentTeamId);
        if (!$team) return null;

        // Vérifier que l'équipe n'a pas atteint le max
        $squadSize = GameContract::where('game_save_id', $gameSave->id)
            ->where('game_team_id', $currentTeamId)
            ->count();

        if ($squadSize >= self::MAX_SQUAD) {
            // Skip ce pick, avancer
            return $this->advanceAndSkip($gameSave, $state, $draft);
        }

        // Vérifier que le joueur est libre, draftable (non fictif) et visible
        // selon la config (listes de joueurs actives/inactives).
        $player = GamePlayer::where('game_save_id', $gameSave->id)
            ->excludingFictional()
            ->visibleForConfig($gameSave)
            ->find($playerId);
        if (!$player) return null;

        $hasContract = GameContract::where('game_save_id', $gameSave->id)
            ->where('game_player_id', $playerId)
            ->exists();

        if ($hasContract) return null;

        // Rancune envers le coach : refuse d'être drafté par l'équipe contrôlée
        if ($this->playerRefusesTeam($gameSave, $player, (int) $currentTeamId)) return null;

        // Coût = salaire hebdo (majoration polyvalence incluse) × semaines restantes × réduction draft
        $seasonLength = $this->getSeasonLength($gameSave);
        $salary       = $player->adjusted_cost;
        $totalCost    = (int) floor($salary * $seasonLength * self::DRAFT_DISCOUNT);

        if ($totalCost > ($team->budget ?? 0)) return null;

        // Exécuter le pick
        $pick = null;
        DB::transaction(function () use ($gameSave, $team, $player, $salary, $seasonLength, $squadSize, $totalCost, &$pick) {            $isStarter = $squadSize < 11;

            // Numéro de maillot
            $usedNumbers = GamePlayer::where('game_save_id', $gameSave->id)
                ->whereHas('contracts', fn($q) => $q->where('game_team_id', $team->id))
                ->pluck('number')
                ->filter()
                ->toArray();

            $nextNumber = 1;
            while (in_array($nextNumber, $usedNumbers)) {
                $nextNumber++;
            }

            // Drafté par un club IA (non humain) : la rancune envers le coach
            // repart à zéro (nouveau départ).
            if (!in_array((int) $team->id, $gameSave->controlledGameTeamIds(), true)
                && (int) ($player->coach_affinity ?? 0) !== 0) {
                $player->coach_affinity = 0;
            }

            $player->number = $nextNumber;
            $player->save();

            GameContract::create([
                'game_save_id'                    => $gameSave->id,
                'game_team_id'                    => $team->id,
                'game_player_id'                  => $player->id,
                'salary'                          => $salary,
                'start_week'                      => 1,
                'end_week'                        => $seasonLength,
                'is_starter'                      => $isStarter,
                'is_captain'                      => false,
                'captain_rerolls_remaining'       => 3,
                'captain_reroll_used_this_action' => false,
            ]);

            $team->budget = max(0, ($team->budget ?? 0) - $totalCost);
            $team->save();

            $pick = [
                'team_id'    => $team->id,
                'team_name'  => $team->name,
                'player_id'  => $player->id,
                'player_name'=> trim($player->firstname . ' ' . $player->lastname),
                'position'   => $player->position,
                'cost'       => $totalCost,
                'total_cost' => $totalCost,
                'round'      => $this->getCurrentRound($gameSave),
                'photo_path' => $player->photo_path,
            ];
        });

        if (!$pick) return null;

        // Enregistrer le pick et avancer
        $draft['picks'][] = $pick;
        $state['draft']   = $draft;
        $gameSave->state  = $state;
        $gameSave->save();

        $this->advancePick($gameSave);

        return $pick;
    }

    /**
     * Avance au pick suivant. Si fin de round, passe au round suivant.
     * Si le draft est terminé (toutes les équipes ont >= MIN_SQUAD), marque completed.
     */
    public function advancePick(GameSave $gameSave): void
    {
        $state = $gameSave->state ?? [];
        $draft = $state['draft'] ?? [];
        $order = $draft['order'] ?? [];
        $total = count($order);

        if ($total === 0) return;

        $pickIndex = ($draft['current_pick_index'] ?? 0) + 1;

        if ($pickIndex >= $total) {
            // Fin du round → vérifier si le draft est fini
            $draft['round']              = ($draft['round'] ?? 1) + 1;
            $draft['current_pick_index'] = 0;

            if ($this->isDraftComplete($gameSave)) {
                $draft['completed'] = true;
            }
        } else {
            $draft['current_pick_index'] = $pickIndex;
        }

        // Skip les équipes qui ont atteint MAX_SQUAD
        if (!($draft['completed'] ?? false)) {
            $draft = $this->skipFullTeams($gameSave, $draft);
        }

        $state['draft']  = $draft;
        $gameSave->state = $state;
        $gameSave->save();
    }

    /**
     * Skip les équipes ayant >= MAX_SQUAD joueurs.
     */
    protected function skipFullTeams(GameSave $gameSave, array $draft): array
    {
        $order = $draft['order'] ?? [];
        $total = count($order);
        $round = $draft['round'] ?? 1;
        $effectiveOrder = ($round % 2 === 0) ? array_reverse($order) : $order;
        $maxAttempts = $total; // Éviter boucle infinie

        for ($i = 0; $i < $maxAttempts; $i++) {
            $pickIndex     = $draft['current_pick_index'] ?? 0;
            $currentTeamId = $effectiveOrder[$pickIndex] ?? null;

            if (!$currentTeamId) break;

            $squadSize = GameContract::where('game_save_id', $gameSave->id)
                ->where('game_team_id', $currentTeamId)
                ->count();

            $finished = $draft['finished_teams'] ?? [];
            if ($squadSize < self::MAX_SQUAD && !in_array($currentTeamId, $finished)) break;

            // Skip
            $pickIndex++;
            if ($pickIndex >= $total) {
                $draft['round']              = ($draft['round'] ?? 1) + 1;
                $draft['current_pick_index'] = 0;

                if ($this->isDraftComplete($gameSave)) {
                    $draft['completed'] = true;
                    break;
                }

                // Recalculer l'ordre pour le nouveau round
                $round = $draft['round'];
                $effectiveOrder = ($round % 2 === 0) ? array_reverse($order) : $order;
            } else {
                $draft['current_pick_index'] = $pickIndex;
            }
        }

        return $draft;
    }

    /**
     * Le draft est terminé quand toutes les équipes ont >= MIN_SQUAD.
     */
    public function isDraftComplete(GameSave $gameSave): bool
    {
        $state  = $gameSave->state ?? [];
        $draft  = $state['draft'] ?? [];
        $finished = $draft['finished_teams'] ?? [];

        $teamIds = GameTeam::where('game_save_id', $gameSave->id)->pluck('id');

        foreach ($teamIds as $teamId) {
            $count = GameContract::where('game_save_id', $gameSave->id)
                ->where('game_team_id', $teamId)
                ->count();

            // Terminé si : a atteint le max OU a dit "fini" (avec min 14)
            if ($count >= self::MAX_SQUAD) continue;
            if (in_array($teamId, $finished) && $count >= self::MIN_SQUAD) continue;

            // Cette équipe n'a pas fini
            return false;
        }

        return true;
    }

    /**
     * Marque une équipe comme ayant fini son draft.
     */
    public function finishTeamDraft(GameSave $gameSave, int $teamId): bool
    {
        $count = GameContract::where('game_save_id', $gameSave->id)
            ->where('game_team_id', $teamId)
            ->count();

        if ($count < self::MIN_SQUAD) return false;

        $state = $gameSave->state ?? [];
        $draft = $state['draft'] ?? [];
        $finished = $draft['finished_teams'] ?? [];

        if (!in_array($teamId, $finished)) {
            $finished[] = $teamId;
        }

        $draft['finished_teams'] = $finished;
        $state['draft'] = $draft;
        $gameSave->state = $state;
        $gameSave->save();

        // Vérifier si tout le monde a fini
        if ($this->isDraftComplete($gameSave)) {
            $draft['completed'] = true;
            $state['draft'] = $draft;
            $gameSave->state = $state;
            $gameSave->save();
            $this->finalizeDraft($gameSave);
            return true;
        }

        // Avancer au prochain pick (skip cette équipe)
        $this->advancePick($gameSave);

        return false;
    }

    /**
     * Retourne l'ID de l'équipe dont c'est le tour.
     */
    /**
     * Un joueur en rupture avec le coach (affinité ≤ seuil) refuse d'être
     * drafté par l'équipe contrôlée. Les équipes IA ne sont pas concernées.
     */
    public function playerRefusesTeam(GameSave $gameSave, GamePlayer $player, int $teamId): bool
    {
        // La rancune envers le coach ne concerne que les équipes humaines.
        if (!in_array($teamId, $gameSave->controlledGameTeamIds(), true)) return false;

        return (int) ($player->coach_affinity ?? 0) <= MoraleService::AFFINITY_REFUSAL_THRESHOLD;
    }

    public function getCurrentTeamId(GameSave $gameSave): ?int
    {
        $draft = ($gameSave->state ?? [])['draft'] ?? null;
        if (!$draft || ($draft['completed'] ?? false)) return null;

        $order     = $draft['order'] ?? [];
        $round     = $draft['round'] ?? 1;
        $pickIndex = $draft['current_pick_index'] ?? 0;

        $effectiveOrder = ($round % 2 === 0) ? array_reverse($order) : $order;
        return $effectiveOrder[$pickIndex] ?? null;
    }

    /**
     * Vérifie si c'est au tour du joueur humain.
     */
    public function isHumanTurn(GameSave $gameSave): bool
    {
        $currentTeamId = $this->getCurrentTeamId($gameSave);
        return $currentTeamId !== null
            && in_array((int) $currentTeamId, $gameSave->controlledGameTeamIds(), true);
    }

    protected function getCurrentRound(GameSave $gameSave): int
    {
        return ($gameSave->state ?? [])['draft']['round'] ?? 1;
    }

    protected function getSeasonLength(GameSave $gameSave): int
    {
        $teamCount = GameTeam::where('game_save_id', $gameSave->id)->count();
        if ($teamCount < 2) return 28;
        return $teamCount % 2 === 1 ? $teamCount * 2 : ($teamCount - 1) * 2;
    }

    protected function advanceAndSkip(GameSave $gameSave, array $state, array $draft): ?array
    {
        $this->advancePick($gameSave);
        return ['skipped' => true];
    }

    /**
     * Finalise le draft : passe en phase season et génère le calendrier.
     */
    /**
     * Finalise le draft : réorganise les lineups de toutes les équipes,
     * puis passe en phase season.
     */
    public function finalizeDraft(GameSave $gameSave): void
    {
        $teams = GameTeam::where('game_save_id', $gameSave->id)
            ->with(['contracts.gamePlayer'])
            ->get();

        $state = $gameSave->state ?? [];

        foreach ($teams as $team) {
            $this->organizeTeamLineup($team, $gameSave, $state);
        }

        $gameSave->phase = 'season';
        $gameSave->state = $state;
        $gameSave->save();
    }

    /**
     * Réorganise le lineup d'une équipe après le draft :
     * - Assigne les 11 meilleurs joueurs comme titulaires selon leur poste et la formation
     * - Place chaque titulaire dans le bon slot de la formation
     * - Le reste en remplaçants
     */
    protected function organizeTeamLineup(GameTeam $team, GameSave $gameSave, array &$state): void
    {
        $contracts = $team->contracts->filter(fn($c) => $c->gamePlayer !== null);
        if ($contracts->isEmpty()) return;

        $formation = $team->formation ?? '4-2-2-2';
        $slotsNeeded = $this->getSlotsByZone($formation);

        // Grouper les joueurs par poste
        $playersByPos = ['GK' => [], 'DEF' => [], 'MID' => [], 'ATT' => []];
        foreach ($contracts as $contract) {
            $p = $contract->gamePlayer;
            $group = $this->positionGroup($p->position ?? '');
            $playersByPos[$group][] = [
                'contract'  => $contract,
                'player'    => $p,
                'overall'   => $this->playerOverall($p),
            ];
        }

        // Trier chaque poste par overall décroissant
        foreach ($playersByPos as &$group) {
            usort($group, fn($a, $b) => $b['overall'] - $a['overall']);
        }

        // Assigner les titulaires slot par slot
        $starters = [];
        $assigned = []; // IDs déjà assignés

        // Zone mapping : 0=GK, 1=DEF, 2=MDF, 3=MOF, 4=ATT
        $zoneToPos = [0 => 'GK', 1 => 'DEF', 2 => 'MID', 3 => 'MID', 4 => 'ATT'];

        foreach ($slotsNeeded as $slot => $zone) {
            $targetPos = $zoneToPos[$zone] ?? 'MID';

            // Chercher le meilleur joueur disponible au bon poste
            $found = null;
            foreach ($playersByPos[$targetPos] as $entry) {
                if (!in_array($entry['player']->id, $assigned)) {
                    $found = $entry;
                    break;
                }
            }

            // Fallback : prendre n'importe quel joueur non assigné
            if (!$found) {
                foreach (['MID', 'DEF', 'ATT', 'GK'] as $fallbackPos) {
                    foreach ($playersByPos[$fallbackPos] as $entry) {
                        if (!in_array($entry['player']->id, $assigned)) {
                            $found = $entry;
                            break 2;
                        }
                    }
                }
            }

            if ($found) {
                $starters[$slot] = $found;
                $assigned[] = $found['player']->id;
            }
        }

        // Mettre à jour is_starter
        $starterIds = array_map(fn($e) => $e['player']->id, $starters);

        foreach ($contracts as $contract) {
            $contract->is_starter = in_array($contract->gamePlayer->id, $starterIds);
            $contract->save();
        }

        // Numéros de maillot cohérents avec le poste et la formation
        $this->assignJerseyNumbers($contracts, $starters, $slotsNeeded);

        // Sauvegarder le lineup dans state
        $lineupSlots = [];
        foreach ($starters as $slot => $entry) {
            $lineupSlots[$slot] = $entry['player']->id;
        }

        $state['lineup'][$team->id] = [
            'formation' => $formation,
            'slots'     => $lineupSlots,
        ];
    }

    /**
     * Parse une formation (ex: "4-2-2-2") et retourne un mapping slot → zone.
     * Zone : 0=GK, 1=DEF, 2=MDF, 3=MOF, 4=ATT
     */
    protected function getSlotsByZone(string $formation): array
    {
        $parts = array_map('intval', explode('-', $formation));
        $slots = [];
        $slotNum = 1;

        // Slot 1 = GK (toujours)
        $slots[$slotNum++] = 0;

        // Les parties de la formation correspondent aux zones 1, 2, 3, 4
        foreach ($parts as $zoneIndex => $count) {
            $zone = $zoneIndex + 1; // 1=DEF, 2=MDF, 3=MOF, 4=ATT
            for ($i = 0; $i < $count; $i++) {
                $slots[$slotNum++] = $zone;
            }
        }

        return $slots;
    }

    /**
     * Attribue des numéros de maillot cohérents avec le poste et la formation.
     *
     * Les titulaires reçoivent en priorité le numéro conventionnel de leur zone
     * (1 gardien ; 2-3-4-5 défense ; 5-6 milieu défensif ; 7-10-8 milieu
     * offensif ; 9-11 attaque), avec résolution « premier libre » en cas de
     * conflit. Les remplaçants prennent ensuite les numéros libres à partir de 12.
     *
     * @param  iterable  $contracts    Contrats de l'équipe (gamePlayer chargé).
     * @param  array     $starters     slot => ['player' => GamePlayer, ...].
     * @param  array     $slotsNeeded  slot => zone (0=GK..4=ATT).
     */
    protected function assignJerseyNumbers(iterable $contracts, array $starters, array $slotsNeeded): void
    {
        // Numéros conventionnels préférés par zone, par ordre de priorité.
        $preferredByZone = [
            0 => [1],                 // Gardien
            1 => [2, 3, 4, 5, 6],     // Défenseurs
            2 => [5, 6, 8, 4],        // Milieux défensifs
            3 => [7, 10, 8, 11],      // Milieux offensifs
            4 => [9, 11, 7, 10],      // Attaquants
        ];

        $used    = [];
        $numbers = []; // player id => numéro

        $nextFree = function (int $from) use (&$used): int {
            $n = $from;
            while (in_array($n, $used, true)) $n++;
            return $n;
        };

        // 1) Titulaires, dans l'ordre des slots (gardien → défense → attaque)
        ksort($starters);
        foreach ($starters as $slot => $entry) {
            $zone = $slotsNeeded[$slot] ?? 2;

            $num = null;
            foreach ($preferredByZone[$zone] ?? [] as $candidate) {
                if (!in_array($candidate, $used, true)) { $num = $candidate; break; }
            }
            $num ??= $nextFree(2);

            $used[]    = $num;
            $numbers[$entry['player']->id] = $num;
        }

        // 2) Remplaçants : numéros libres restants à partir de 12
        foreach ($contracts as $contract) {
            $pid = $contract->gamePlayer->id;
            if (isset($numbers[$pid])) continue;

            $num       = $nextFree(12);
            $used[]    = $num;
            $numbers[$pid] = $num;
        }

        // 3) Persistance
        foreach ($contracts as $contract) {
            $p = $contract->gamePlayer;
            if (isset($numbers[$p->id])) {
                $p->number = $numbers[$p->id];
                $p->save();
            }
        }
    }

    /**
     * Regroupe un poste précis en grand groupe (GK/DEF/MID/ATT).
     * Miroir de DraftAIService/AITransferService::positionGroup.
     */
    protected function positionGroup(string $position): string
    {
        $p = strtoupper(trim($position));
        if (str_contains($p, 'GK') || str_contains($p, 'GOAL'))    return 'GK';
        if (str_contains($p, 'DEF') || str_contains($p, 'BACK'))   return 'DEF';
        if (str_contains($p, 'MDF') || str_contains($p, 'MID') || str_contains($p, 'MOF')) return 'MID';
        if (str_contains($p, 'ATT') || str_contains($p, 'FOR'))    return 'ATT';
        return 'MID';
    }

    /**
     * Calcule l'overall d'un joueur.
     */
    protected function playerOverall($player): int
    {
        if (!$player) return 0;
        $stats = [
            $player->attack ?? 0, $player->defense ?? 0,
            $player->shot ?? 0, $player->pass ?? 0,
            $player->dribble ?? 0, $player->speed ?? 0,
            $player->tackle ?? 0, $player->block ?? 0,
            $player->intercept ?? 0,
        ];
        return (int) round(array_sum($stats) / max(1, count($stats)));
    }
}
