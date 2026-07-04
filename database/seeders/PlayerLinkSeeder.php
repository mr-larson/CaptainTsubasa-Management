<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class PlayerLinkSeeder extends Seeder
{
    /**
     * Duos d'alchimie [slug A, slug B, nom du duo]. Les slugs suivent la même
     * convention que PlayerSeeder (Str::slug("prenom nom")). Deux joueurs liés
     * débloquent l'action « une-deux » en match quand ils sont tous deux sur
     * le terrain. Duos volontairement iconiques et limités : la valeur vient
     * de leur rareté.
     */
    private const DUOS = [
        // Captain Tsubasa
        ['tsubasa-ozora',        'taro-misaki',      'Golden Combi'],
        ['kazuo-tachibana',      'masao-tachibana',  'Jumeaux Tachibana'],
        ['kojiro-hyuga',         'takeshi-sawada',   'Duo de Toho'],
        ['teppei-kisugi',        'hajime-taki',      'Duo de Nankatsu'],
        ['karl-heinz-schneider', 'hermann-kaltz',    'Duo de la Mannschaft'],
        // Blue Lock
        ['yoichi-isagi',         'meguru-bachira',   'Duo de Blue Lock'],
        ['seishiro-nagi',        'reo-mikage',       'Nagi & Reo'],
        // Ao Ashi
        ['ashito-aoi',           'eisaku-ohtomo',    'Amis d\'Esperion'],
        // Hungry Heart
        ['kyosuke-kano',         'rafael-del-franco', 'Duo d\'Akanegaoka'],
        // École des Champions
        ['nino-biancchi',        'riki-biancchi',    'Frères Biancchi'],
    ];

    public function run(): void
    {
        Schema::disableForeignKeyConstraints();
        DB::table('player_links')->truncate();
        Schema::enableForeignKeyConstraints();

        // Index slug → id sur tout le catalogue (même convention que PlayerSeeder)
        $idBySlug = [];
        foreach (DB::table('players')->get(['id', 'firstname', 'lastname']) as $p) {
            $idBySlug[Str::slug($p->firstname . ' ' . $p->lastname)] = $p->id;
        }

        $now  = now();
        $rows = [];
        foreach (self::DUOS as [$slugA, $slugB, $label]) {
            // Validation stricte : un slug inconnu (joueur renommé/supprimé du
            // seed) fait échouer le seed plutôt que de créer un duo fantôme.
            foreach ([$slugA, $slugB] as $slug) {
                if (!isset($idBySlug[$slug])) {
                    throw new \RuntimeException(
                        "Duo « {$label} » : joueur introuvable pour le slug « {$slug} ». "
                        . 'Vérifie les données de PlayerSeeder ou corrige PlayerLinkSeeder.'
                    );
                }
            }

            $rows[] = [
                'player_a_id' => $idBySlug[$slugA],
                'player_b_id' => $idBySlug[$slugB],
                'label'       => $label,
                'created_at'  => $now,
                'updated_at'  => $now,
            ];
        }

        DB::table('player_links')->insert($rows);
    }
}
