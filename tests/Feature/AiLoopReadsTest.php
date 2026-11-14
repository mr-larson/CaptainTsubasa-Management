<?php

namespace Tests\Feature;

use App\Models\GameSaves\GameBonusCard;
use App\Models\GameSaves\GameDeclaration;
use App\Models\GameSaves\GameSave;
use App\Models\GameSaves\GameTeam;
use App\Services\AiBonusCardService;
use App\Services\AiCoachService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\BuildsGameWorld;
use Tests\TestCase;

/**
 * Boucles IA par équipe (entraîneur, cartes) : leurs lectures sont faites une fois pour toute
 * la ligue, sans changer les décisions. Le scénario « malus puis équipe suivante » épingle la
 * seule situation où l'ordre des équipes compte.
 */
class AiLoopReadsTest extends TestCase
{
    use RefreshDatabase;
    use BuildsGameWorld;

    /** Nombre de requêtes SELECT dont le SQL correspond à $pattern pendant $action. */
    private function selectsMatching(string $pattern, callable $action): int
    {
        $count = 0;
        DB::listen(function ($query) use (&$count, $pattern) {
            if (preg_match('/^\s*select\b/i', $query->sql) && preg_match($pattern, $query->sql)) {
                $count++;
            }
        });
        $action();

        return $count;
    }

    // ───────────────────────── Entraîneur IA ─────────────────────────

    /**
     * Semaine 2, matchs de la semaine 1 joués : les équipes IA vont par paires, plus un match
     * humain. Chaque équipe IA a un titulaire en forme (3 duels gagnés).
     */
    private function coachWorld(int $aiPairs): GameSave
    {
        $save = $this->makeSave(['week' => 2]);
        [$human, $humanPlayers] = $this->makeTeamWithSquad($save, ['is_controlled' => true, 'human_seat' => 1]);
        $save->update(['controlled_game_team_id' => $human->id]);

        $stats = [];
        $ai    = [];
        foreach (range(1, $aiPairs * 2) as $_) {
            [$team, $players] = $this->makeTeamWithSquad($save);
            $ai[] = $team;
            $stats[$players[0]->id] = ['duelsWon' => 3];
        }
        [$filler, $fillerPlayers] = $this->makeTeamWithSquad($save);
        $stats[$fillerPlayers[0]->id] = ['duelsWon' => 3];

        for ($i = 0; $i < count($ai); $i += 2) {
            $this->makeMatch($save, $ai[$i], $ai[$i + 1], ['status' => 'played', 'week' => 1, 'match_stats' => ['players' => $stats]]);
            $this->makeMatch($save, $ai[$i], $ai[$i + 1], ['week' => 2]);
        }
        $this->makeMatch($save, $human, $filler, ['status' => 'played', 'week' => 1, 'match_stats' => ['players' => $stats]]);
        $this->makeMatch($save, $human, $filler, ['week' => 2]);

        return $save->fresh();
    }

    public function test_the_coach_checks_recent_declarations_once_for_the_whole_league(): void
    {
        $few  = $this->coachWorld(1);
        $many = $this->coachWorld(4);
        $coach = app(AiCoachService::class);

        $readsFew  = $this->selectsMatching('/from `game_declarations`/', fn () => $coach->prepareForWeek($few, 1));
        $readsMany = $this->selectsMatching('/from `game_declarations`/', fn () => $coach->prepareForWeek($many, 1));

        $this->assertSame(1, $readsFew);
        $this->assertSame(1, $readsMany, 'Un contrôle par équipe est revenu.');
    }

    public function test_every_ai_team_still_praises_its_player_in_form_and_the_cooldown_still_applies(): void
    {
        $save  = $this->coachWorld(3);
        $coach = app(AiCoachService::class);

        $coach->prepareForWeek($save, 1);
        $declared = GameDeclaration::count();
        $coach->prepareForWeek($save, 1); // rejeu : le délai interdit une seconde déclaration

        $this->assertSame(7, $declared, 'Une félicitation par équipe IA : 3 paires et l\'adversaire du manager humain.');
        $this->assertSame($declared, GameDeclaration::count());
        $this->assertSame(['praise'], GameDeclaration::pluck('type')->unique()->values()->all());
    }

    // ───────────────────────── Cartes IA ─────────────────────────

    /** Offre de boutique telle que le tirage la fabrique. */
    private function offer(\App\Models\BonusCard $card, string $tier = 'bronze'): array
    {
        return [
            'bonus_card_id' => $card->id, 'kind' => $card->kind, 'tier' => $tier, 'cost' => 0, 'name' => $card->name,
            'description' => $card->description, 'icon' => $card->icon, 'target' => $card->target->value,
            'execution_phase' => $card->execution_phase, 'effect_type' => $card->effect_type, 'effect_value' => $card->effect_value,
        ];
    }

    /** Une partie : un humain et $ai équipes IA par paires, tous avec budget et un match cette semaine. */
    private function cardsWorld(int $ai): array
    {
        $save = $this->makeSave();
        [$human] = $this->makeTeamWithSquad($save, ['is_controlled' => true, 'human_seat' => 1, 'budget' => 5000]);
        $save->update(['controlled_game_team_id' => $human->id]);

        $teams = [];
        foreach (range(1, $ai) as $_) {
            [$team] = $this->makeTeamWithSquad($save, ['budget' => 5000]);
            $teams[] = $team;
        }
        for ($i = 0; $i + 1 < count($teams); $i += 2) {
            $this->makeMatch($save, $teams[$i], $teams[$i + 1]);
        }

        return [$save->fresh(), $teams];
    }

    private function putShop(GameSave $save, array $offersByTeam): GameSave
    {
        $save->gameState()->putShop((int) $save->season, (int) $save->week, $offersByTeam);
        $save->save();

        return $save->fresh();
    }

    public function test_card_decisions_read_injuries_and_pending_cards_once_for_the_whole_league(): void
    {
        $dull = $this->makeBonusCard(['effect_type' => 'revenue_performance']); // jamais acheté par l'IA à ce budget
        $reads = [];
        foreach ([2, 8] as $count) {
            [$save, $teams] = $this->cardsWorld($count);
            $save = $this->putShop($save, collect($teams)->mapWithKeys(fn ($t) => [(string) $t->id => [$this->offer($dull)]])->all());

            $reads[$count] = [
                'injuries' => $this->selectsMatching('/from `game_injuries`/', fn () => app(AiBonusCardService::class)->processWeek($save)),
            ];
            $this->assertSame(1, $reads[$count]['injuries'], "Avec {$count} équipes IA : une seule lecture des blessures attendue.");
        }
    }

    public function test_a_malus_played_by_one_team_is_seen_by_the_next_team_deciding(): void
    {
        $drain = $this->makeBonusCard(['kind' => 'malus', 'target' => 'opponent', 'effect_type' => 'opponent_morale_drain', 'effect_value' => ['amount' => 30]]);
        $heal  = $this->makeBonusCard(['effect_type' => 'morale_boost', 'target' => 'player', 'effect_value' => ['amount' => 20]]);

        [$save, [$a, $b]] = $this->cardsWorld(2); // A et B s'affrontent cette semaine ; A est traitée avant B
        $save = $this->putShop($save, [
            (string) $a->id => [$this->offer($drain)],
            (string) $b->id => [$this->offer($heal)],
        ]);

        app(AiBonusCardService::class)->processWeek($save);

        // A a bien joué son malus (le moral de B a chuté)…
        $this->assertSame(30, (int) $b->players()->min('morale'));
        // …et B, qui décide ensuite, a LU ce moral abaissé : elle a acheté et utilisé la carte de soin.
        $this->assertTrue(
            GameBonusCard::where('game_team_id', $b->id)->where('bonus_card_id', $heal->id)->where('status', 'used')->exists(),
            "L'équipe B aurait dû voir le moral abaissé par le malus de A et se soigner."
        );
    }

    public function test_without_a_malus_a_healthy_team_does_not_buy_a_healing_card(): void
    {
        $heal = $this->makeBonusCard(['effect_type' => 'morale_boost', 'target' => 'player', 'effect_value' => ['amount' => 20]]);
        [$save, [$a, $b]] = $this->cardsWorld(2);
        $save = $this->putShop($save, [(string) $a->id => [$this->offer($heal)], (string) $b->id => [$this->offer($heal)]]);

        app(AiBonusCardService::class)->processWeek($save);

        $this->assertSame(0, GameBonusCard::count(), 'Moral normal : aucune carte de soin n\'est utile.');
    }

    // ───────────────────────── Duos IA ─────────────────────────

    public function test_an_ai_duo_attempt_confirms_the_duo_without_rereading_the_league(): void
    {
        $save = $this->makeSave();
        [$human] = $this->makeTeamWithSquad($save, ['is_controlled' => true, 'human_seat' => 1]);
        $save->update(['controlled_game_team_id' => $human->id]);
        [, $players] = $this->makeTeamWithSquad($save);
        foreach (range(1, 3) as $_) {
            $this->makeTeamWithSquad($save);
        }

        // Un duo éligible dans l'équipe IA : score élevé, plusieurs matchs en commun, jamais tenté.
        [$a, $b] = [$players[0]->id, $players[1]->id];
        [$a, $b] = $a < $b ? [$a, $b] : [$b, $a];
        \App\Models\GameSaves\GamePlayerChemistry::forceCreate([
            'game_save_id' => $save->id, 'player_a_id' => $a, 'player_b_id' => $b, 'score' => 100, 'shared_matches' => 5,
        ]);

        $service = new class extends \App\Services\PlayerChemistryService {
            protected function roll(): int
            {
                return 1; // le dé réussit toujours
            }
        };

        $reads = $this->selectsMatching('/from `game_player_chemistries`/', fn () => $service->attemptForAiTeams($save->fresh()));

        $row = \App\Models\GameSaves\GamePlayerChemistry::where('player_a_id', $a)->where('player_b_id', $b)->first();
        $this->assertTrue((bool) $row->confirmed, 'Le duo éligible est confirmé par l\'IA.');
        $this->assertSame(5, (int) $row->last_attempt_at_match_count);
        $this->assertLessThanOrEqual(2, $reads, 'Alchimies et duos liés lus une fois pour la ligue, pas pour la tentative.');
    }
}
