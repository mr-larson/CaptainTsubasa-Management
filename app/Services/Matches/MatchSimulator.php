<?php

namespace App\Services\Matches;

use App\Contracts\Randomizer;
use App\Helpers\PlayerLuck;
use App\Models\GameSaves\GameMatch;
use App\Models\GameSaves\GameSave;
use App\Models\GameSaves\GameTeam;
use App\Services\Cards\BonusCardActivationService;
use App\Services\Matches\Engine\ActionChooser;
use App\Services\Matches\Engine\DuelModifiers;
use App\Services\Matches\Engine\CrossResolver;
use App\Services\Matches\Engine\MatchRoster;
use App\Services\Matches\Engine\MatchState;
use App\Services\Matches\Engine\MatchStats;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use App\Services\Players\DeclarationService;
use App\Services\Players\MoraleService;
use App\Services\Players\PlayerChemistryService;
use App\Services\Players\PostMatchProgressionService;

/**
 * Simulation automatique d'un match, pour les équipes IA et les matchs que le joueur
 * ne dispute pas : tours successifs (30 par défaut) où le ballon progresse par zones et où
 * chaque action est un duel attaque contre défense (dés à 20 faces, stats, poste, moral,
 * fatigue, avantage du domicile). Produit le score, le déroulé, les bilans de joueurs et les
 * fautes, puis enregistre le match, le classement, les sanctions et blessures, la
 * progression des joueurs et l'alchimie.
 *
 * Les calculs sont répartis dans app/Services/MatchEngine/ : DuelModifiers (force des
 * joueurs dans un duel), MatchRoster (qui joue), ActionChooser (choix de l'action),
 * CrossResolver (centres et passes longues) et MatchStats. Ce miroir PHP du moteur
 * client (resources/js/Pages/Match/engine/*) tire tout son hasard du Randomizer injecté :
 * avec une graine fixée, un match se rejoue à l'identique.
 */
class MatchSimulator
{
    /**
     * Reçoit les services appelés après le match et les briques du moteur. Le Randomizer
     * rend la simulation rejouable ; DuelModifiers, MatchRoster et ActionChooser portent
     * les calculs détaillés dans app/Services/MatchEngine/.
     */
    public function __construct(
        private BonusCardActivationService $bonusCardActivationService,
        private MatchLineupSnapshot $matchLineupSnapshot,
        private FoulAndInjuryService $foulAndInjuryService,
        private PostMatchProgressionService $postMatchProgressionService,
        private PlayerChemistryService $playerChemistryService,
        private DeclarationService $declarationService,
        private Randomizer $random,
        private DuelModifiers $mods,
        private MatchRoster $roster,
        private ActionChooser $chooser,
    ) {
    }

    private const MAX_TURNS                       = 30;
    private const MAX_ZONE_INDEX                  = DuelModifiers::MAX_ZONE_INDEX;   // 5 zones, index 0..4
    private const ENDURANCE_DEFAULT               = DuelModifiers::ENDURANCE_DEFAULT;
    private const GOOD_COUNTER_BONUS              = DuelModifiers::GOOD_COUNTER_BONUS;
    private const HOME_BONUS                      = DuelModifiers::HOME_BONUS;
    private const SHOT_DISTANCE_PENALTY_PER_ZONE  = 1;

    // Fautes / cartons des duels de champ (miroir de engine/fouls.js) :
    // 1 naturel du défenseur = faute grave (rouge 15 %, jaune 75 %, faute 10 %) ;
    // égalité parfaite = 25 % de faute, dont 20 % sanctionnée d'un jaune.
    // Les blessures découlent des fautes côté FoulAndInjuryService, comme pour
    // un match joué : les équipes simulées subissent le même régime que l'humain.
    private const TIE_FOUL_CHANCE                 = 0.25;
    // Blessure d'épuisement (miroir fieldDuel.js / shot.js) : stamina à 0 après le duel.
    private const EXHAUSTION_INJURY_CHANCE_FIELD  = 0.30;
    private const EXHAUSTION_INJURY_CHANCE_SHOT   = 0.40;

    // Usure passive par tour pour chaque titulaire (miroir client). Combinée au
    // `staminaMax = stat stamina`, rend la fatigue de fin de match visible.
    private const PASSIVE_STAMINA_DRAIN_PER_TURN  = 0.4;

    /**
     * Passer l'instance GameSave de l'appelant : la progression post-match
     * écrit dans son `state`. Sur une copie rechargée, ces clés seraient
     * écrasées par la prochaine sauvegarde de l'instance de l'appelant.
     */
    public function simulateOtherMatchesOfWeek(GameMatch $playedMatch, ?GameSave $gameSave = null): void
    {
        $gameSave ??= GameSave::find($playedMatch->game_save_id);

        $others = GameMatch::query()
            ->where('game_save_id', $playedMatch->game_save_id)
            ->where('week', $playedMatch->week)
            ->where('status', 'scheduled')
            ->where('id', '!=', $playedMatch->id)
            ->with([
                'homeTeam' => fn($q) => $q->with(['contracts' => fn($cq) => $cq->activeAt($playedMatch->week)->with('gamePlayer')]),
                'awayTeam' => fn($q) => $q->with(['contracts' => fn($cq) => $cq->activeAt($playedMatch->week)->with('gamePlayer')]),
            ])
            ->get();

        // Tout ou rien : un match à moitié enregistré (score sans classement,
        // progression sans fautes…) ne doit jamais survivre à une erreur.
        DB::transaction(function () use ($others, $gameSave) {
            // Le verrou de la partie est pris une fois pour toute la série (et non à chaque match).
            if ($gameSave) {
                GameSave::whereKey($gameSave->id)->lockForUpdate()->first();
            }
            foreach ($others as $m) {
                $this->simulateAndSave($m, $gameSave, saveLocked: (bool) $gameSave);
            }
        });
    }

    /**
     * Simule une liste de matchs programmés (ceux déjà joués sont ignorés) en une seule
     * transaction : une erreur annule tous les matchs de la série. Le GameSave de l'appelant,
     * quand il est fourni, sert à toute la série pour que la progression écrite dans son
     * `state` ne soit pas écrasée.
     */
    public function simulateMatchesCollection(Collection $matches, ?GameSave $gameSave = null): void
    {
        // Une seule instance par save : chaque match lit et enrichit le même state.
        $saves = $gameSave ? [(int) $gameSave->id => $gameSave] : [];

        // Atomique par appel, quel que soit l'appelant (contrôleur sous verrou,
        // clôture de semaine, commande artisan) : une erreur sur un match annule
        // aussi les matchs déjà simulés de la même série, jamais de semaine à moitié écrite.
        DB::transaction(function () use ($matches, &$saves) {
            $locked = []; // parties dont le verrou est déjà pris dans cette transaction

            foreach ($matches as $m) {
                if ($m->status !== \App\Enums\MatchStatus::Scheduled) continue;

                $m->loadMissing([
                    'homeTeam' => fn($q) => $q->with(['contracts' => fn($cq) => $cq->activeAt($m->week)->with('gamePlayer')]),
                    'awayTeam' => fn($q) => $q->with(['contracts' => fn($cq) => $cq->activeAt($m->week)->with('gamePlayer')]),
                ]);

                // Une partie lue ici est verrouillée par la même requête ; une partie fournie par
                // l'appelant est verrouillée une seule fois, au premier match qui la concerne.
                $saveId = (int) $m->game_save_id;
                if (! isset($saves[$saveId])) {
                    $saves[$saveId] = GameSave::lockForUpdate()->find($saveId);
                    $locked[$saveId] = true;
                } elseif (! isset($locked[$saveId])) {
                    GameSave::whereKey($saveId)->lockForUpdate()->first();
                    $locked[$saveId] = true;
                }
                $this->simulateAndSave($m, $saves[$saveId], saveLocked: $saves[$saveId] !== null);
            }
        });
    }

    /** Résolveur des centres / passes longues, créé à la demande avec les mêmes DuelModifiers (rôles du match). */
    private ?CrossResolver $crossResolver = null;

    /** Le résolveur de centres et passes longues, créé au premier usage. */
    private function crossResolver(): CrossResolver
    {
        return $this->crossResolver ??= new CrossResolver($this->random, $this->mods, $this->roster, $this->chooser);
    }

    // ==========================
    //   SIMULATION PRINCIPALE
    // ==========================
    private function simulateAndSave(GameMatch $m, ?GameSave $gameSave, bool $saveLocked = false): void
    {
        $lineups  = $gameSave?->gameState()->lineups() ?? [];

        // Malus « titulaire consigné » à appliquer pour ce match (par équipe).
        // On se contente de LIRE le malus ici : la consommation est centralisée
        // en fin de semaine sur l'instance GameSave du contrôleur (évite tout
        // conflit d'instances dans la boucle de simulation).
        $benchedByTeam = [];
        $modsByTeam    = [];
        if ($gameSave) {
            $activation = $this->bonusCardActivationService;
            $benchedByTeam = [
                (int) $m->home_team_id => $activation->getBenchedPlayerIds($gameSave, (int) $m->home_team_id),
                (int) $m->away_team_id => $activation->getBenchedPlayerIds($gameSave, (int) $m->away_team_id),
            ];
            $modsByTeam = [
                (int) $m->home_team_id => $activation->getMatchStatModifiers($gameSave, (int) $m->home_team_id, $m->id),
                (int) $m->away_team_id => $activation->getMatchStatModifiers($gameSave, (int) $m->away_team_id, $m->id),
            ];
        }

        // Blessés et suspendus ne jouent pas : ils sont écartés et remplacés
        // comme les titulaires consignés par un malus. Indispensable pour les
        // équipes humaines simulées (simulate-week), dont le lineup n'est pas
        // réajusté par AiLineUpService.
        $unavailable = $this->roster->unavailablePlayerIds((int) $m->game_save_id, (int) $m->week);
        if (!empty($unavailable)) {
            foreach ([(int) $m->home_team_id, (int) $m->away_team_id] as $teamId) {
                $benchedByTeam[$teamId] = array_merge($benchedByTeam[$teamId] ?? [], $unavailable);
            }
        }

        $maxTurns = (int) ($gameSave?->getConfig('match_max_turns') ?? self::MAX_TURNS);

        [$homeScore, $awayScore, $matchStats] = $this->simulateMatch(
            $m->homeTeam,
            $m->awayTeam,
            $lineups,
            $benchedByTeam,
            $modsByTeam,
            $maxTurns,
            (bool) ($gameSave?->getConfig('match_criticals_enabled') ?? true),
        );

        // Les fautes ne sont pas stockées dans match_stats : elles deviennent
        // blessures / cartons / suspensions via le même service qu'un match joué.
        $foulEvents = $matchStats['foul_events'] ?? [];
        unset($matchStats['foul_events']);

        if ($gameSave) {
            $m->setRelation('gameSave', $gameSave); // évite de relire la partie pour chaque match
        }
        // Les équipes ont déjà leurs contrats actifs de la semaine (chargés plus haut) : on les réutilise.
        $matchStats['lineups'] = $this->matchLineupSnapshot->build($m, contractsPreloaded: true);

        $m->home_score  = $homeScore;
        $m->away_score  = $awayScore;
        $m->status      = 'played';
        $m->match_stats = $matchStats;
        $m->save();

        if ($gameSave && !empty($foulEvents)) {
            $this->foulAndInjuryService->processMatchEvents($gameSave, $m, $foulEvents);
        }

        // Progression post-match pour les joueurs IA
        if ($gameSave) {
            $this->postMatchProgressionService->applyForMatch($gameSave, $m);
            $this->playerChemistryService->recordMatch($gameSave, $m, simulated: true, saveAlreadyLocked: $saveLocked);
            $this->declarationService->consumeMatchBoosts($gameSave, $m->id);
        }

        $this->applyResultToStandings($m);
    }

    /**
     * Simule un match complet et retourne [homeScore, awayScore, matchStats].
     * matchStats est au même format que celui produit par engine.js côté client.
     */
    /**
     * Moteur de simulation "complet" en 30 tours par défaut (réglable), miroir du moteur client
     * (resources/js/Pages/Match/engine/*) : progression par zones (0..4),
     * duels RPS (pierre-feuille-ciseaux) attaque/défense, gestion de la
     * stamina et bonus du domicile. Produit un `match_stats` strictement
     * compatible avec celui généré par `buildMatchStats` côté client
     * (mêmes clés consommées par PlayerStatsService / PostMatchProgressionService / TabCalendar).
     */
    private function simulateMatch(GameTeam $home, GameTeam $away, array $lineups = [], array $benchedByTeam = [], array $modsByTeam = [], int $maxTurns = self::MAX_TURNS, bool $criticalsEnabled = true): array
    {
        $homePlayers = $this->roster->getStarters($home, $benchedByTeam[(int) $home->id] ?? []);
        $awayPlayers = $this->roster->getStarters($away, $benchedByTeam[(int) $away->id] ?? []);

        // Modificateurs "ce match" (boosts pre_match + debuffs subis) : mutation
        // en mémoire uniquement (les joueurs ne sont pas sauvegardés ici ; la
        // progression recharge via find()).
        $this->roster->applyMatchStatModifiers($homePlayers, $modsByTeam[(int) $home->id] ?? []);
        $this->roster->applyMatchStatModifiers($awayPlayers, $modsByTeam[(int) $away->id] ?? []);

        $this->mods->assignRoles([$home, $away], $lineups);

        $state = $this->startMatch($home, $away, $homePlayers, $awayPlayers, $criticalsEnabled);

        for ($turn = 1; $turn <= $maxTurns; $turn++) {
            $this->playTurn($state, $turn);
        }

        return $this->buildResult($state, $maxTurns);
    }

    /** Prépare l'état initial : gardiens, endurance de départ de chaque titulaire, bilans à zéro, équipe qui engage tirée au sort. */
    private function startMatch(GameTeam $home, GameTeam $away, Collection $homePlayers, Collection $awayPlayers, bool $criticalsEnabled): MatchState
    {
        $state = new MatchState(
            $home, $away, $homePlayers, $awayPlayers,
            $this->roster->getGoalkeeper($homePlayers),
            $this->roster->getGoalkeeper($awayPlayers),
            $criticalsEnabled,
        );

        foreach ($state->allPlayers() as $p) {
            // Endurance max = stat `stamina` (miroir du client) : un joueur peu
            // endurant atteint plus vite les paliers de fatigue. Repli sur 100.
            $max = (float) (($p->stamina ?? 0) > 0 ? $p->stamina : self::ENDURANCE_DEFAULT);
            $state->staminaMax[(string) $p->id] = $max;
            $state->stamina[(string) $p->id]    = $max;
        }

        $state->side = $this->random->int(0, 1) ? 'home' : 'away';

        return $state;
    }

    /** Joue un tour : usure, choix de l'action, duel (ou centre / passe longue), enregistrement des bilans, coût d'endurance, blessure d'épuisement, puis progression ou changement de possession du ballon. */
    private function playTurn(MatchState $s, int $turn): void
    {
        $isHomeAttacking = $s->side === 'home';
        $attTeam    = $isHomeAttacking ? $s->home        : $s->away;
        $attPlayers = $isHomeAttacking ? $s->homePlayers : $s->awayPlayers;
        $defPlayers = $isHomeAttacking ? $s->awayPlayers : $s->homePlayers;
        $defGK      = $isHomeAttacking ? $s->awayGK      : $s->homeGK;
        $defSide    = $isHomeAttacking ? 'away'          : 'home';

        $this->drainPassiveStamina($s);

        $action = $this->chooseOffenseAction($s, $attTeam, $attPlayers);

        // ----- Centre / Passe longue : résolution dédiée (checks multiples) -----
        if ($action === 'cross' || $action === 'long_pass') {
            $this->crossResolver()->resolve(
                $action, $attPlayers, $defPlayers, $defGK, $s->stamina, $s->staminaMax,
                $s->playerStats, $s->teamStats, $s->events, $s->goalEvents,
                $s->side, $defSide, $s->zone, $s->frontOfKeeper, $turn, $isHomeAttacking
            );
            return;
        }

        $duel = $this->resolveDuel($s, $turn, $action, $attPlayers, $defPlayers, $defGK, $isHomeAttacking);
        [$attacker, $defender, $defAction, $defCategory, $success] = [
            $duel['attacker'], $duel['defender'], $duel['defAction'], $duel['defCategory'], $duel['success'],
        ];

        // ----- Fautes / cartons (duel de champ uniquement, comme le moteur) -----
        if ($defCategory === 'defenseField' && $defender && $attacker) {
            $this->rollFoul($s->foulEvents, $attacker, $defender, $s->criticalsEnabled && $duel['defenseRoll'] === 1, $duel['attackScore'] == $duel['defenseScore']);
        }

        $isGoal = $success && $action === 'shot';

        // ----- Déroulé du match (action par action), miroir de `state.matchLog`
        //       côté moteur client : exploité par le résumé de match (TabCalendar). -----
        $s->events[] = $this->buildEventEntry($turn, $s->side, $action, $defAction, $success, $isGoal, $attacker, $defender, $s->zone, $s->frontOfKeeper);

        $this->recordDuelStats($s, $turn, $defSide, $action, $defAction, $success, $isGoal, $attacker, $defender);

        // ----- Coût de stamina (action + déplacement de balle) -----
        $this->mods->applyStaminaCost($s->stamina, $attacker, $action, 'attack', $duel['discountAttack']);
        $this->mods->applyStaminaCost($s->stamina, $defender, $defAction, $defCategory, $duel['discountDefense']);

        $this->rollExhaustionInjuries($s, $action, $attacker, $defender);

        $this->advanceBall($s, $action, $success, $defSide);
    }

    /** Usure passive : chaque titulaire perd un peu d'endurance à chaque tour (en plus des coûts d'action). Placé en tête de tour pour s'appliquer aussi aux tours centre / passe longue. */
    private function drainPassiveStamina(MatchState $s): void
    {
        if (self::PASSIVE_STAMINA_DRAIN_PER_TURN <= 0) {
            return;
        }

        foreach ($s->allPlayers() as $p) {
            $pid = (string) ($p->id ?? 0);
            if (($s->stamina[$pid] ?? 0) > 0) {
                $s->stamina[$pid] = max(0.0, $s->stamina[$pid] - self::PASSIVE_STAMINA_DRAIN_PER_TURN);
            }
        }
    }

    /** Action offensive du tour : tir forcé face au gardien, tir ou dribble en zone de finition, sinon choix selon le style de l'équipe. */
    private function chooseOffenseAction(MatchState $s, GameTeam $attTeam, Collection $attPlayers): string
    {
        if ($s->frontOfKeeper || $s->zone >= self::MAX_ZONE_INDEX) {
            return $s->frontOfKeeper
                ? 'shot'
                : ($this->random->int(1, 10) <= 6 ? 'shot' : 'dribble');
        }

        return $this->chooser->randomOffenseAction($attTeam->tactical_style, $s->zone, $attPlayers);
    }

    /**
     * Duel du tour : choisit l'attaquant et le défenseur, calcule leurs scores (stats, contexte,
     * endurance, distance au but, domicile, dés, critiques naturels) et laisse un joueur très
     * satisfait relancer un duel perdu (« Dépassement de soi », consigné dans le déroulé).
     *
     * @return array{attacker: mixed, defender: mixed, defAction: string, defCategory: string, success: bool,
     *               attackScore: float, defenseScore: float, defenseRoll: int, discountAttack: bool, discountDefense: bool}
     */
    private function resolveDuel(MatchState $s, int $turn, string $action, Collection $attPlayers, Collection $defPlayers, $defGK, bool $isHomeAttacking): array
    {
        $attacker = $this->roster->pickPlayerForAction($attPlayers, $action);

        // ----- Choix du défenseur (miroir RPS du client) -----
        if ($action === 'shot') {
            $defender    = $defGK;
            $defAction   = $this->random->int(0, 1) ? 'hands' : 'punch';
            $defCategory = 'defenseGK';
        } else {
            $defAction   = $action === 'pass' ? 'intercept' : 'tackle';
            $defender    = $this->roster->pickPlayerForAction($defPlayers, $defAction);
            $defCategory = 'defenseField';
        }

        // ----- Calcul des scores de duel (RPS + contexte + stamina + domicile) -----
        // Contexte : `attack` (zone) + `speed` (dribble) côté attaque ;
        //            `defense` (zone) + `speed` (tackle/intercept) côté défense.
        $attackBase = $this->mods->statForAction($attacker, $action, 'attack')
            * $this->mods->attackContextMultiplier($attacker, $action, $s->zone)
            * $this->mods->staminaFactorOf($attacker, $s->stamina, $s->staminaMax);

        if ($action === 'shot' && !$s->frontOfKeeper) {
            $zonesToGoal = self::MAX_ZONE_INDEX - $s->zone;
            $attackBase -= $zonesToGoal * self::SHOT_DISTANCE_PENALTY_PER_ZONE;
        }

        $defenseBase = $this->mods->statForAction($defender, $defAction, $defCategory)
            * $this->mods->defenseContextMultiplier($defender, $defAction, $defCategory, $s->zone)
            * $this->mods->staminaFactorOf($defender, $s->stamina, $s->staminaMax);

        // `block` (dernier rempart) : le moteur serveur envoie tous les tirs au
        // gardien (pas de duel de contre dédié), alors le meilleur contreur de
        // champ se jette devant la frappe et renforce l'arrêt — d'autant plus
        // que le tir part de près du but (zone haute).
        if ($action === 'shot') {
            $blocker = $this->roster->fieldPlayers($defPlayers)->sortByDesc(fn($p) => $p->block ?? 0)->first();
            if ($blocker) $defenseBase *= $this->mods->blockEdgeMultiplier($blocker, $s->zone);
        }

        // RPS : la défense "attendue" (intercept/tackle/gardien) compte comme bon contre
        $defenseBase += self::GOOD_COUNTER_BONUS;

        // Bonus du domicile : ajouté au score de l'équipe à domicile, qu'elle attaque ou défende
        if ($isHomeAttacking) $attackBase  += self::HOME_BONUS;
        else                  $defenseBase += self::HOME_BONUS;

        $attackRoll  = $this->d20();
        $defenseRoll = $this->d20();

        $attackScore  = $attackBase  + $attackRoll;
        $defenseScore = $defenseBase + $defenseRoll;

        // Critique naturel : un 20 force la victoire de ce côté
        $attackCrit  = $s->criticalsEnabled && $attackRoll === 20;
        $defenseCrit = $s->criticalsEnabled && $defenseRoll === 20;

        if ($attackCrit && !$defenseCrit)      $success = true;
        elseif ($defenseCrit && !$attackCrit)  $success = false;
        else                                   $success = $attackScore > $defenseScore;

        $discountAttack = $attackCrit;
        $discountDefense = $defenseCrit;

        // ----- Moment héroïque : un joueur très satisfait peut relancer un duel perdu -----
        if (
            !$success && $attacker
            && (int) ($attacker->morale ?? MoraleService::NEUTRAL_MORALE) > MoraleService::HEROIC_MORALE_THRESHOLD
            && empty($s->heroicUsed[(string) $attacker->id])
            && $this->random->int(1, 100) <= (int) round(MoraleService::HEROIC_CHANCE * 100)
        ) {
            $s->heroicUsed[(string) $attacker->id] = true;

            $attackRoll  = $this->d20();
            $defenseRoll = $this->d20();
            $attackScore  = $attackBase  + $attackRoll;
            $defenseScore = $defenseBase + $defenseRoll;
            $attackCrit  = $s->criticalsEnabled && $attackRoll === 20;
            $defenseCrit = $s->criticalsEnabled && $defenseRoll === 20;

            $discountAttack = $discountAttack || $attackCrit;
            $discountDefense = $discountDefense || $defenseCrit;

            if ($attackCrit && !$defenseCrit)      $success = true;
            elseif ($defenseCrit && !$attackCrit)  $success = false;
            else                                   $success = $attackScore > $defenseScore;

            $s->events[] = [
                'turn'       => $turn,
                'actionType' => 'heroic',
                'team'       => $s->side,
                'result'     => $success ? 'success' : 'fail',
                'text'       => "🔥 Dépassement de soi — {$attacker->lastname}",
                'details'    => [$success ? '✓ Le duel est renversé !' : '✗ Le duel reste perdu'],
                'diceTag'    => round($attackScore, 1) . '-' . round($defenseScore, 1),
                'zone'            => $s->zone,
                'lane'            => null,
                'front_of_keeper' => $s->frontOfKeeper,
                'action'          => $action,
                'def_action'      => $defAction,
                'attacker'        => MatchStats::eventPlayerRef($attacker),
                'defender'        => MatchStats::eventPlayerRef($defender),
            ];
        }

        return [
            'attacker' => $attacker, 'defender' => $defender,
            'defAction' => $defAction, 'defCategory' => $defCategory,
            'success' => $success,
            'attackScore' => $attackScore, 'defenseScore' => $defenseScore, 'defenseRoll' => $defenseRoll,
            'discountAttack' => $discountAttack, 'discountDefense' => $discountDefense,
        ];
    }

    /** Enregistre le duel dans les bilans : stats de l'attaquant et du défenseur, buts, tentatives et duels gagnés / perdus de chaque équipe. */
    private function recordDuelStats(MatchState $s, int $turn, string $defSide, string $action, string $defAction, bool $success, bool $isGoal, $attacker, $defender): void
    {
        $side = $s->side;

        if ($attacker) {
            $pid = (string) $attacker->id;
            $s->playerStats[$pid] = $s->playerStats[$pid] ?? MatchStats::emptyPlayerStats();
            $s->playerStats[$pid]['offense'][$action]['attempts']++;
            if ($success) $s->playerStats[$pid]['offense'][$action]['success']++;
            if ($isGoal) {
                $s->playerStats[$pid]['offense']['goals']++;
                $s->goalEvents[] = ['player_id' => $attacker->id, 'turn' => $turn];
            }
            $success ? $s->playerStats[$pid]['duelsWon']++ : $s->playerStats[$pid]['duelsLost']++;
        }

        if ($defender) {
            $pid = (string) $defender->id;
            $s->playerStats[$pid] = $s->playerStats[$pid] ?? MatchStats::emptyPlayerStats();
            $s->playerStats[$pid]['defense'][$defAction]['attempts']++;
            if (!$success) $s->playerStats[$pid]['defense'][$defAction]['success']++;
            $success ? $s->playerStats[$pid]['duelsLost']++ : $s->playerStats[$pid]['duelsWon']++;
        }

        // ----- Stats d'équipe (tentatives, indépendamment du résultat) -----
        if ($action === 'pass')         $s->teamStats[$side]['passes']++;
        elseif ($action === 'dribble')  $s->teamStats[$side]['dribbles']++;
        else                            $s->teamStats[$side]['shots']++;

        if ($success) {
            $s->teamStats[$side]['duelsWon']++;
            $s->teamStats[$defSide]['duelsLost']++;
            if ($isGoal) $s->teamStats[$side]['goals']++;
        } else {
            $s->teamStats[$side]['duelsLost']++;
            $s->teamStats[$defSide]['duelsWon']++;
        }
    }

    /** Blessure d'épuisement (stamina à 0 après le duel), comme le moteur client : au plus une par joueur et par match. */
    private function rollExhaustionInjuries(MatchState $s, string $action, $attacker, $defender): void
    {
        $exhaustionChance = $action === 'shot' ? self::EXHAUSTION_INJURY_CHANCE_SHOT : self::EXHAUSTION_INJURY_CHANCE_FIELD;
        foreach ([$attacker, $defender] as $p) {
            if (!$p || isset($s->exhaustedInjured[(int) $p->id])) continue;
            if (($s->stamina[(string) $p->id] ?? 1) <= 0 && $this->random->int(1, 10000) <= $exhaustionChance * 10000) {
                $s->exhaustedInjured[(int) $p->id] = true;
                $s->foulEvents[] = ['type' => 'injury', 'player_id' => (int) $p->id, 'severity' => 'light'];
            }
        }
    }

    /** Progression du ballon après le duel : tir (reprise côté défenseur), passe ou dribble réussi (zone suivante ou face au gardien), perte de balle (possession inversée, zone en miroir). */
    private function advanceBall(MatchState $s, string $action, bool $success, string $defSide): void
    {
        if ($action === 'shot') {
            // Le ballon repart de zéro côté défenseur, qu'il y ait but ou arrêt
            $s->side          = $defSide;
            $s->zone          = 0;
            $s->frontOfKeeper = false;
        } elseif ($success) {
            if ($action === 'pass') {
                $s->zone = min(self::MAX_ZONE_INDEX, $s->zone + 1);
            } elseif ($action === 'dribble') {
                if ($s->zone < self::MAX_ZONE_INDEX) $s->zone++;
                else                                 $s->frontOfKeeper = true;
            }
        } else {
            // Perte de balle : possession inversée, la zone est mise en miroir
            $s->side          = $defSide;
            $s->zone          = self::MAX_ZONE_INDEX - $s->zone;
            $s->frontOfKeeper = false;
        }
    }

    /** Assemble le résultat : [buts domicile, buts visiteur, match_stats]. Pas de remplacements ici : chaque titulaire joue tous les tours, les remplaçants n'entrent pas. */
    private function buildResult(MatchState $s, int $maxTurns): array
    {
        $starters = [
            'home' => $s->homePlayers->map(fn($p) => (string) $p->id)->values()->all(),
            'away' => $s->awayPlayers->map(fn($p) => (string) $p->id)->values()->all(),
        ];
        $playtime = [];
        foreach ($s->allPlayers() as $p) {
            $playtime[(string) $p->id] = $maxTurns;
        }

        $matchStats = [
            'teams'    => $s->teamStats,
            'players'  => $s->playerStats,
            'events'   => $s->events,
            'foul_events' => $s->foulEvents,
            'starters' => $starters,
            'playtime' => $playtime,
        ];

        return [$s->teamStats['home']['goals'], $s->teamStats['away']['goals'], $matchStats];
    }

    /**
     * Construit une entrée de "déroulé du match" (action par action), au format
     * miroir de `state.matchLog` côté moteur client (ui.js::pushLogEntry), pour
     * que le résumé de match (TabCalendar) puisse afficher le play-by-play aussi
     * bien pour les matchs simulés par l'IA que pour les matchs joués.
     *
     * Forme : { turn, actionType, team, result, text, details, diceTag }
     */
    private function buildEventEntry(
        int $turn, string $side, string $action, string $defAction,
        bool $success, bool $isGoal, $attacker, $defender, int $zone, bool $frontOfKeeper
    ): array {
        $actionLabels = [
            'pass' => 'Passe', 'dribble' => 'Dribble', 'shot' => 'Tir',
            'intercept' => 'Interception', 'tackle' => 'Tacle',
            'block' => 'Contre', 'hands' => 'Arrêt (mains)', 'punch' => 'Dégagement (poing)',
        ];

        $attackerName = $attacker?->lastname ?? 'Joueur';
        $defenderName = $defender?->lastname ?? 'Adversaire';
        $actionLabel  = $actionLabels[$action] ?? ucfirst($action);

        if ($isGoal) {
            $actionType = 'goal';
            $result     = 'attack';
            $text       = "⚽ BUT ! {$attackerName} marque";
        } elseif ($action === 'shot') {
            $actionType = 'shot';
            $result     = $success ? 'attack' : 'defense';
            $text       = $success
                ? "{$actionLabel} de {$attackerName} — cadré"
                : "{$actionLabel} de {$attackerName} — arrêté par {$defenderName}";
        } else {
            $actionType = $action; // 'pass' | 'dribble'
            $result     = $success ? 'attack' : 'defense';
            $text       = $success
                ? "{$actionLabel} réussi(e) par {$attackerName}"
                : "{$actionLabel} de {$attackerName} contré(e) par {$defenderName}";
        }

        $details = [
            "Zone " . ($zone + 1),
            "Défense : {$actionLabels[$defAction]} ({$defenderName})",
        ];
        if ($frontOfKeeper) $details[] = 'Face au gardien';

        return [
            'turn'       => $turn,
            'actionType' => $actionType,
            'team'       => $side === 'home' ? 'internal' : 'external',
            'result'     => $result,
            'text'       => $text,
            'details'    => $details,
            'diceTag'    => null,
            // Données structurées pour le replay visuel (positions + acteurs).
            // lane = null : la simulation PHP n'a pas de notion de couloir.
            'zone'            => $zone,
            'lane'            => null,
            'front_of_keeper' => $frontOfKeeper,
            'action'          => $action,
            'def_action'      => $defAction,
            'attacker'        => MatchStats::eventPlayerRef($attacker),
            'defender'        => MatchStats::eventPlayerRef($defender),
        ];
    }

    // ==========================
    //   HELPERS DUEL / STAMINA
    // ==========================

    // ==========================
    //   MULTIPLICATEURS CONTEXTUELS (rôle distinct par stat)
    // ==========================

    // ==========================
    //   HELPERS JOUEURS
    // ==========================

    /**
     * Faute éventuelle sur un duel de champ (miroir de resolveFoulOutcome, engine/fouls.js).
     */
    private function rollFoul(array &$foulEvents, $attacker, $defender, bool $defenseCritFail, bool $isTie): void
    {
        $defId = (int) $defender->id;
        $attId = (int) $attacker->id;
        $multiplier = PlayerLuck::incidentMultiplier($defender->luck ?? null);

        if ($defenseCritFail) {
            $foulEvents[] = ['type' => 'foul', 'fouler_player_id' => $defId, 'victim_player_id' => $attId, 'is_crit_fail' => true];
            $r = $this->random->int(0, 9999) / 10000;
            if ($r < PlayerLuck::CRITICAL_RED) {
                $foulEvents[] = ['type' => 'card', 'player_id' => $defId, 'card_type' => 'red'];
            } elseif ($r < PlayerLuck::CRITICAL_RED + PlayerLuck::CRITICAL_YELLOW * $multiplier) {
                $foulEvents[] = ['type' => 'card', 'player_id' => $defId, 'card_type' => 'yellow'];
            }
        } elseif ($isTie && $this->random->int(0, 9999) < self::TIE_FOUL_CHANCE * 10000) {
            $foulEvents[] = ['type' => 'foul', 'fouler_player_id' => $defId, 'victim_player_id' => $attId, 'is_crit_fail' => false];
            if ($this->random->int(0, 9999) < PlayerLuck::TIE_YELLOW * $multiplier * 10000) {
                $foulEvents[] = ['type' => 'card', 'player_id' => $defId, 'card_type' => 'yellow'];
            }
        }
    }

    /** Jet de dé à 20 faces (via le Randomizer injecté). */
    private function d20(): int
    {
        return $this->random->int(1, 20);
    }

    // ==========================
    //   STANDINGS
    // ==========================

    /**
     * Met à jour le classement des deux équipes d'après le score du match joué :
     * victoire, nul ou défaite, buts pour et contre. Sans effet si le match n'est pas joué.
     */
    private function applyResultToStandings(GameMatch $m): void
    {
        if ($m->status !== \App\Enums\MatchStatus::Played) return;
        if ($m->home_score === null || $m->away_score === null) return;

        $home = $m->relationLoaded('homeTeam') ? $m->homeTeam : $m->homeTeam()->first();
        $away = $m->relationLoaded('awayTeam') ? $m->awayTeam : $m->awayTeam()->first();

        if (!$home || !$away) return;

        if ($m->home_score > $m->away_score) {
            $home->wins   = ($home->wins   ?? 0) + 1;
            $away->losses = ($away->losses ?? 0) + 1;
        } elseif ($m->home_score < $m->away_score) {
            $away->wins   = ($away->wins   ?? 0) + 1;
            $home->losses = ($home->losses ?? 0) + 1;
        } else {
            $home->draws  = ($home->draws  ?? 0) + 1;
            $away->draws  = ($away->draws  ?? 0) + 1;
        }

        $home->goals_for     = ($home->goals_for     ?? 0) + $m->home_score;
        $home->goals_against = ($home->goals_against ?? 0) + $m->away_score;
        $away->goals_for     = ($away->goals_for     ?? 0) + $m->away_score;
        $away->goals_against = ($away->goals_against ?? 0) + $m->home_score;

        $home->save();
        $away->save();
    }

}
