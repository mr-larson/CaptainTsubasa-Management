<?php

namespace Database\Seeders;

use App\Models\BonusCard;
use Database\Seeders\BonusCards;
use Illuminate\Database\Seeder;

/**
 * Catalogue des cartes bonus / malus. Les données vivent par famille dans
 * database/seeders/BonusCards/ ; l'ordre ci-dessous est celui d'insertion
 * (il détermine les ids d'une base neuve). Idempotent : mise à jour par nom.
 */
class BonusCardSeeder extends Seeder
{
    /** @var array<int, class-string> */
    private const FAMILIES = [
        BonusCards\StaminaCards::class,
        BonusCards\InjuryCards::class,
        BonusCards\FinanceCards::class,
        BonusCards\MatchBoostCards::class,
        BonusCards\MoraleCards::class,
        BonusCards\CoachRelationCards::class,
        BonusCards\MalusFatigueAndBenchCards::class,
        BonusCards\MalusTargetedCards::class,
        BonusCards\PreMatchTeamBoostCards::class,
    ];

    /** @return array<int, array<string, mixed>> */
    public static function cards(): array
    {
        return array_merge(...array_map(fn (string $family) => $family::cards(), self::FAMILIES));
    }

    public function run(): void
    {
        // Anciennes cartes finance "argent gratuit" remplacées par les paris/défis.
        // Le FK game_bonus_cards.bonus_card_id est en cascadeOnDelete : supprimer
        // ces cartes retire aussi les exemplaires déjà achetés dans les parties.
        BonusCard::whereIn('name', ['Bonus sponsors', 'Contrat TV', 'Méga-sponsor'])->delete();

        foreach (self::cards() as $card) {
            BonusCard::updateOrCreate(['name' => $card['name']], $card + ['cost' => BonusCard::SHOP_PRICE]);
        }
    }
}
