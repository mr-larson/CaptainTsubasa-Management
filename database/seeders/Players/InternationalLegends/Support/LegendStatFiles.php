<?php

namespace Database\Seeders\Players\InternationalLegends\Support;

/**
 * Lit les fichiers de stats par nation (Countries/stats/*.json), source de vérité des
 * retouches individuelles des légendes. Chaque fichier donne, par nom complet, les seules
 * stats à remplacer (fusionnées sur celles du profil) et la raison du choix.
 */
final class LegendStatFiles
{
    /** Dossier des fichiers de stats, un par nation. */
    public static function directory(): string
    {
        return dirname(__DIR__).'/Countries/stats';
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
