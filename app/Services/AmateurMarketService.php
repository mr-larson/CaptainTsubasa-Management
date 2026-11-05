<?php

namespace App\Services;

use App\Models\GameSaves\GamePlayer;
use App\Models\GameSaves\GameSave;

/**
 * Filet de sécurité du marché des transferts : garantit en permanence un petit
 * contingent d'« amateurs » libres (origin = amateur), nettement plus faibles
 * que les joueurs de la base (stats 8-13 contre 15+ ; salaire 8-15 contre 22+).
 *
 * But : qu'un club à court d'effectif ou d'argent puisse toujours compléter son
 * onze, sans rendre le jeu plus facile — les bons joueurs restent rares et
 * disputés avec l'IA, qui n'engage d'amateur qu'en cas d'urgence (< 11 joueurs).
 */
class AmateurMarketService
{
    /** Nombre minimum d'amateurs libres par poste. */
    public const FLOOR_BY_POSITION = [
        'Goalkeeper' => 1,
        'Defender'   => 2,
        'Midfielder' => 2,
        'Forward'    => 2,
    ];

    private const FIRSTNAMES = [
        'Akio', 'Daichi', 'Eiji', 'Fumio', 'Goro', 'Haruki', 'Isamu', 'Jiro', 'Kazuki', 'Kenta',
        'Makoto', 'Naoki', 'Osamu', 'Riku', 'Satoru', 'Shinji', 'Takumi', 'Tetsuo', 'Yuji', 'Yusuke',
    ];

    private const LASTNAMES = [
        'Abe', 'Endo', 'Fujita', 'Goto', 'Hayashi', 'Ikeda', 'Kato', 'Kimura', 'Kobayashi', 'Maeda',
        'Mori', 'Murata', 'Nakano', 'Ogawa', 'Okada', 'Saito', 'Sakamoto', 'Shimizu', 'Ueno', 'Yamada',
    ];

    /**
     * Complète le marché jusqu'au plancher par poste. Retourne le nombre d'amateurs créés.
     */
    public function ensureFloor(GameSave $gameSave): int
    {
        // Coupe du Monde : pas de marché des transferts.
        if ($gameSave->competition_type === 'world_cup') return 0;

        $week = (int) ($gameSave->week ?? 1);

        $freeByPosition = GamePlayer::where('game_save_id', $gameSave->id)
            ->where('origin', GamePlayer::ORIGIN_AMATEUR)
            ->whereDoesntHave('contracts', fn($q) => $q->where('game_save_id', $gameSave->id)->activeAt($week))
            ->get()
            ->countBy('position');

        $created = 0;
        foreach (self::FLOOR_BY_POSITION as $position => $floor) {
            for ($i = (int) ($freeByPosition[$position] ?? 0); $i < $floor; $i++) {
                $this->generate($gameSave, $position);
                $created++;
            }
        }

        return $created;
    }

    public function generate(GameSave $gameSave, string $position): GamePlayer
    {
        $low  = fn() => random_int(6, 10);   // stats hors poste
        $main = fn() => random_int(10, 13);  // stats du poste (la base démarre à 15)

        $isGK  = $position === 'Goalkeeper';
        $isDef = $position === 'Defender';
        $isMid = $position === 'Midfielder';
        $isAtt = $position === 'Forward';

        return GamePlayer::create([
            'game_save_id'        => $gameSave->id,
            'firstname'           => self::FIRSTNAMES[array_rand(self::FIRSTNAMES)],
            'lastname'            => self::LASTNAMES[array_rand(self::LASTNAMES)],
            'position'            => $position,
            'origin'              => GamePlayer::ORIGIN_AMATEUR,
            'nationality'         => \App\Enums\Nationality::JAPON,
            'secondary_positions' => [],
            'description'         => 'Joueur amateur du coin : peu coûteux, de quoi compléter un effectif.',
            'speed'               => random_int(25, 40),
            'stamina'             => random_int(55, 75),
            'attack'              => ($isAtt || $isMid) ? $main() : $low(),
            'defense'             => ($isDef || $isGK) ? $main() : $low(),
            'shot'                => $isAtt ? $main() : $low(),
            'pass'                => $isMid ? $main() : $low(),
            'dribble'             => ($isAtt || $isMid) ? $main() : $low(),
            'block'               => $isDef ? $main() : $low(),
            'intercept'           => ($isDef || $isMid) ? $main() : $low(),
            'tackle'              => $isDef ? $main() : $low(),
            'heading'             => $low(),
            'hand_save'           => $isGK ? $main() : 0,
            'punch_save'          => $isGK ? $main() : 0,
            'special_moves'       => [],
            'cost'                => random_int(8, 15),
            'morale'              => MoraleService::NEUTRAL_MORALE,
        ]);
    }
}
