<?php

namespace Database\Seeders\Players\HungryHeart;

/**
 * Lit les fichiers de stats de Hungry Heart (HungryHeart/stats/*.json), source de vérité des retouches
 * individuelles des joueurs de Hungry Heart. Même format que les fichiers par pays
 * des légendes : par nom complet, les seules stats à remplacer et la raison du choix.
 */
final class HungryHeartStatFiles
{
    /** Dossier des fichiers de stats, un seul fichier. */
    public static function directory(): string
    {
        return __DIR__.'/stats';
    }

    /**
     * Toutes les retouches, indexées par nom complet.
     *
     * @return array<string, array<string, int>>
     */
    public static function overrides(): array
    {
        $overrides = [];
        foreach (glob(self::directory().'/*.json') ?: [] as $file) {
            $data = json_decode(file_get_contents($file), true, 512, JSON_THROW_ON_ERROR);
            foreach ($data['players'] ?? [] as $name => $entry) {
                $overrides[$name] = $entry['stats'];
            }
        }

        return $overrides;
    }

    /** Stats d'un joueur après application de sa retouche éventuelle. */
    public static function apply(string $fullName, array $stats): array
    {
        static $overrides = null;
        $overrides ??= self::overrides();

        return isset($overrides[$fullName]) ? array_replace($stats, $overrides[$fullName]) : $stats;
    }
}
