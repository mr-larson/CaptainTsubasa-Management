<?php

namespace App\Services;

use App\Contracts\Randomizer;
use App\Models\GameSaves\GameMatch;
use App\Models\GameSaves\GameSave;

/**
 * Met une sauvegarde « en état de jouer » : mandat de la direction, défis de
 * saison, calendrier, compositions IA de la semaine.
 *
 * prepare() est idempotente et s'appelle SOUS LE VERROU de la sauvegarde, aux
 * transitions explicites : création d'une partie hors draft, fin de draft
 * (qui ouvre chaque saison). La page Play n'y recourt plus que comme filet de
 * sécurité (anciennes sauvegardes), uniquement quand needsPreparation() le dit,
 * et après l'avoir revérifié sous verrou : deux onglets ne génèrent donc jamais
 * deux fois le calendrier ou les défis.
 */
class SeasonBootstrapService
{

    public function __construct(private Randomizer $random)
    {
    }
    public function prepare(GameSave $gameSave): void
    {
        if (! $gameSave->isInMatchPhase()) return;

        if (($gameSave->phase ?? 'season') === 'season') {
            app(CareerObjectiveService::class)->ensureSeasonMandate($gameSave);
            $challenges = app(SeasonChallengeService::class);
            $challenges->ensureSeasonChallenges($gameSave);
            // Rattrape les objectifs déjà réussis dans les sauvegardes existantes.
            $challenges->payCompleted($gameSave, [(int) $gameSave->controlled_game_team_id]);
        }

        $this->ensureCalendar($gameSave);
        app(AiLineUpService::class)->prepareScheduledTeams($gameSave, (int) ($gameSave->week ?? 1));
    }

    /**
     * Lecture seule : vrai si prepare() (ou la purge du draft terminé) a
     * quelque chose à faire. Coûte au plus une requête (existence du calendrier).
     */
    public function needsPreparation(GameSave $gameSave): bool
    {
        // state.draft résiduel une fois la draft terminée (cf. DraftService::purgeFinishedDraft).
        if (! in_array($gameSave->phase, ['draft', 'intersaison_draft'], true) && $gameSave->gameState()->hasDraft()) {
            return true;
        }

        if (! $gameSave->isInMatchPhase()) return false;

        $season = (int) ($gameSave->season ?? 1);

        if (($gameSave->phase ?? 'season') === 'season') {
            $career = app(CareerObjectiveService::class);
            if ($career->isEnabled($gameSave)) {
                $data = $career->data($gameSave);
                if ($data['status'] === 'active' && ($data['mandate']['season'] ?? null) !== $season) return true;
            }

            if (app(SeasonChallengeService::class)->isEnabled($gameSave)
                && $gameSave->gameState()->challengesSeason() !== $season) {
                return true;
            }
        }

        return $gameSave->competition_type !== 'world_cup' && ! $gameSave->matches()->exists();
    }

    public function ensureCalendar(GameSave $gameSave): void
    {
        // En Coupe du Monde, le calendrier (poules + élimination directe) est
        // géré par le moteur de tournoi (Phase 4), pas par le round-robin de ligue.
        if ($gameSave->competition_type === 'world_cup') return;

        if ($gameSave->matches()->exists()) return;

        $teamIds   = $gameSave->gameTeams()->pluck('id')->all();
        $teamCount = count($teamIds);
        if ($teamCount < 2) return;

        // Mélanger pour éviter que la même équipe soit toujours extérieure
        $teamIds = $this->random->shuffle($teamIds);

        if ($teamCount % 2 === 1) {
            $teamIds[] = null;
            $teamCount++;
        }

        $rounds = $teamCount - 1;
        $half   = $teamCount / 2;
        $ids    = $teamIds;
        $matches = [];

        for ($round = 0; $round < $rounds; $round++) {
            $weekAller  = $round + 1;
            $weekRetour = $round + 1 + $rounds;

            for ($i = 0; $i < $half; $i++) {
                $home = $ids[$i];
                $away = $ids[$teamCount - 1 - $i];
                if ($home === null || $away === null) continue;

                // Alterner qui reçoit à l'aller vs retour
                if ($round % 2 === 0) {
                    $matches[] = ['week' => $weekAller,  'home' => $home, 'away' => $away];
                    $matches[] = ['week' => $weekRetour, 'home' => $away, 'away' => $home];
                } else {
                    $matches[] = ['week' => $weekAller,  'home' => $away, 'away' => $home];
                    $matches[] = ['week' => $weekRetour, 'home' => $home, 'away' => $away];
                }
            }

            $fixed = array_shift($ids);
            $last  = array_pop($ids);
            array_unshift($ids, $fixed);
            array_splice($ids, 1, 0, [$last]);
        }

        foreach ($matches as $m) {
            GameMatch::create([
                'game_save_id' => $gameSave->id,
                'week'         => $m['week'],
                'home_team_id' => $m['home'],
                'away_team_id' => $m['away'],
                'status'       => 'scheduled',
            ]);
        }
    }
}
