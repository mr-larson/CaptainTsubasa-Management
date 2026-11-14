<?php

namespace App\Services\Draft;

use App\Models\GameSaves\GameSave;
use App\Models\GameSaves\GameTeam;
use App\Services\Concerns\EvaluatesPlayers;
use App\Support\GameState;

/**
 * Composition initiale d'une équipe : onze titulaires placés par poste selon la formation,
 * remplaçants, numéros de maillot cohérents avec le poste. Utilisée à la fin de la draft et
 * à la création d'une partie « prebuilt » (hors draft).
 */
class DraftLineupOrganizer
{
    use EvaluatesPlayers;

    /**
     * Réorganise le lineup d'une équipe après le draft (ou à la création
     * d'une partie "prebuilt" hors draft) :
     * - Assigne les 11 meilleurs joueurs comme titulaires selon leur poste
     *   (principal ou secondaire) et la formation
     * - Place chaque titulaire dans le bon slot de la formation
     * - Le reste en remplaçants
     */
    public function organize(GameTeam $team, GameSave $gameSave, GameState $state): void
    {
        $contracts = $team->contracts->filter(fn($c) => $c->gamePlayer !== null);
        if ($contracts->isEmpty()) return;

        $formation = $team->formation ?? '4-2-2-2';
        $slotsNeeded = $this->getSlotsByZone($formation);

        // Assigner les titulaires slot par slot
        $starters = [];
        $assigned = []; // IDs déjà assignés

        // Zone mapping : 0=GK, 1=DEF, 2=MDF, 3=MOF, 4=ATT
        $zoneToPos = [0 => 'GK', 1 => 'DEF', 2 => 'MID', 3 => 'MID', 4 => 'ATT'];

        foreach ($slotsNeeded as $slot => $zone) {
            $targetPos = $zoneToPos[$zone] ?? 'MID';

            $available = $contracts->reject(fn($c) => in_array($c->gamePlayer->id, $assigned, true));

            // Meilleur joueur disponible sachant jouer ce poste (principal ou secondaire)
            $found = $available
                ->filter(fn($c) => in_array($targetPos, $this->playerPositionGroups($c->gamePlayer), true))
                ->sortByDesc(fn($c) => $this->playerOverall($c->gamePlayer, $targetPos))
                ->first();

            // Fallback : aucun joueur ne maîtrise ce poste. On évite de mettre
            // un gardien pur sur le terrain ou un joueur de champ dans les
            // buts, sauf si c'est vraiment le seul joueur restant.
            if (!$found) {
                $fallbackPool = $targetPos === 'GK'
                    ? $available->filter(fn($c) => $this->positionGroup($c->gamePlayer->position ?? '') === 'GK')
                    : $available->reject(fn($c) => $this->positionGroup($c->gamePlayer->position ?? '') === 'GK');

                if ($fallbackPool->isEmpty()) {
                    $fallbackPool = $available;
                }

                $found = $fallbackPool
                    ->sortByDesc(fn($c) => $this->playerOverall($c->gamePlayer, $targetPos))
                    ->first();
            }

            if ($found) {
                $starters[$slot] = ['contract' => $found, 'player' => $found->gamePlayer];
                $assigned[] = $found->gamePlayer->id;
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

        $state->setLineup($team->id, ['formation' => $formation, 'slots' => $lineupSlots]);
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
}
