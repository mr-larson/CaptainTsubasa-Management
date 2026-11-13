<?php

namespace Database\Seeders\Support;

use App\Enums\Nationality;

/**
 * Nationalité d'un joueur de référence. Sert à composer les sélections
 * nationales du mode Coupe du Monde. Résolution :
 *   override par slug  >  défaut par origin  >  null
 */
final class PlayerNationality
{
    /**
     * Nationalité par défaut selon l'œuvre de provenance (origin).
     * Les univers japonais (Captain Tsubasa au collège, Blue Lock, Ao Ashi,
     * Hungry Heart) sont des équipes nippones ; École des Champions est italien
     * majoritaire. Les exceptions sont gérées au cas par cas par les maps
     * nationalities() de chaque classe Players\*. `null` = non sélectionnable.
     */
    private const DEFAULT_BY_ORIGIN = [
        'captain_tsubasa'     => 'Japon',
        'blue_lock'           => 'Japon',
        'ao_ashi'             => 'Japon',
        'hungry_heart'        => 'Japon',
        'ecole_des_champions' => 'Italie',
        'original'            => null,
    ];

    /** @param array<string, string> $overridesBySlug */
    public static function resolve(string $slug, string $origin, array $overridesBySlug): ?string
    {
        $nationality = $overridesBySlug[$slug] ?? self::DEFAULT_BY_ORIGIN[$origin] ?? null;

        // Validation stricte : une nationalité non reconnue (faute de frappe,
        // langue mélangée comme « Sweden » au lieu de « Suède ») fait échouer
        // le seed plutôt que de créer une nation fantôme. Pour un nouveau pays,
        // ajoute-le d'abord dans App\Enums\Nationality.
        if ($nationality !== null && !Nationality::isValid($nationality)) {
            throw new \RuntimeException(
                "Nationalité inconnue « {$nationality} » pour le joueur « {$slug} ». "
                . "Ajoute-la dans App\\Enums\\Nationality ou corrige la valeur."
            );
        }

        return $nationality;
    }
}
