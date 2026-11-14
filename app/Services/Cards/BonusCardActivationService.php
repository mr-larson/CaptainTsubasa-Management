<?php

namespace App\Services\Cards;

use App\Models\GameSaves\GameBonusCard;
use App\Models\GameSaves\GameSave;
use Illuminate\Validation\ValidationException;

/**
 * Point d'entrée des cartes bonus/malus activées (par le joueur ou par l'IA).
 * Vérifie la carte et sa cible, applique l'effet via CardEffectRegistry (une classe par type d'effet,
 * cf. Effects/) puis la marque utilisée. Les autres méthodes délèguent à PreMatchCardService
 * (boosts avant match), SponsorChallengeService (défis) et MalusCardEffects (malus).
 */
class BonusCardActivationService
{
    public function __construct(private CardEffectRegistry $effects)
    {
    }

    /**
     * Active une carte depuis le tab Bonus (cartes immediate).
     * Les cartes pre_match sont activées automatiquement dans MatchSimulator.
     *
     * @param  GameBonusCard  $gameBonusCard
     * @param  GameSave       $gameSave
     * @param  int|null       $targetPlayerId  (requis pour target=player)
     */
    public function activate(
        GameBonusCard $gameBonusCard,
        GameSave $gameSave,
        ?int $targetPlayerId = null,
        ?int $targetTeamId = null
    ): array {
        if (!$gameBonusCard->isAvailable()) {
            throw ValidationException::withMessages(['card' => 'Cette carte a déjà été utilisée.']);
        }

        $card = $gameBonusCard->bonusCard;

        // Valider le joueur cible si nécessaire
        if ($card->target === \App\Enums\BonusCardTarget::Player) {
            if (!$targetPlayerId) {
                throw ValidationException::withMessages(['card' => 'Vous devez sélectionner un joueur cible.']);
            }
            $gameBonusCard->target_player_id = $targetPlayerId;
        }

        $effect = $this->effects->for((string) $card->effect_type)
            ?? throw ValidationException::withMessages(['card' => 'Type d\'effet inconnu.']);

        $result = $effect->apply($gameSave, $gameBonusCard, $targetPlayerId, $targetTeamId);

        // Marquer comme utilisée
        $gameBonusCard->status     = 'used';
        $gameBonusCard->used_season = $gameSave->season;
        $gameBonusCard->used_week   = $gameSave->week;
        $gameBonusCard->save();

        return $result;
    }

    // ──────────────────────────────────────────────────────────
    //   DÉLÉGATIONS (API conservée pour les appelants existants)
    // ──────────────────────────────────────────────────────────

    /** @see PreMatchCardService::getActivePreMatchBoosts() */
    public function getActivePreMatchBoosts(GameSave $gameSave, int $teamId): array
    {
        return app(PreMatchCardService::class)->getActivePreMatchBoosts($gameSave, $teamId);
    }

    /** @see PreMatchCardService::getPreMatchStatBoosts() */
    public function getPreMatchStatBoosts(GameSave $gameSave, int $teamId): array
    {
        return app(PreMatchCardService::class)->getPreMatchStatBoosts($gameSave, $teamId);
    }

    /** @see PreMatchCardService::getMatchStatModifiers() */
    public function getMatchStatModifiers(GameSave $gameSave, int $teamId, ?int $matchId = null): array
    {
        return app(PreMatchCardService::class)->getMatchStatModifiers($gameSave, $teamId, $matchId);
    }

    /** @see PreMatchCardService::consumePreMatchCards() */
    public function consumePreMatchCards(GameSave $gameSave, int $teamId): void
    {
        app(PreMatchCardService::class)->consumePreMatchCards($gameSave, $teamId);
    }

    /** @see PreMatchCardService::consumePreMatchCardsForPlayedWeek() */
    public function consumePreMatchCardsForPlayedWeek(GameSave $gameSave, int $week): void
    {
        app(PreMatchCardService::class)->consumePreMatchCardsForPlayedWeek($gameSave, $week);
    }

    /** @see SponsorChallengeService::resolveSponsorChallenges() */
    public function resolveSponsorChallenges(GameSave $gameSave, int $week): array
    {
        return app(SponsorChallengeService::class)->resolveSponsorChallenges($gameSave, $week);
    }

    /** @see SponsorChallengeService::evaluateChallenge() */
    public static function evaluateChallenge(string $challenge, int $scored, int $against): bool
    {
        return SponsorChallengeService::evaluateChallenge($challenge, $scored, $against);
    }

    /** @return int[] ids des joueurs consignés par un malus « titulaire consigné ». */
    public function getBenchedPlayerIds(GameSave $gameSave, int $teamId): array
    {
        return app(MalusCardEffects::class)->getBenchedPlayerIds($gameSave, $teamId);
    }

    /** Délègue à MalusCardEffects le retrait des malus « titulaire consigné » dont l'équipe cible a joué cette semaine (appelé une fois en fin de semaine). */
    public function consumeMalusForPlayedWeek(GameSave $gameSave, int $week): void
    {
        app(MalusCardEffects::class)->consumeMalusForPlayedWeek($gameSave, $week);
    }
}
