<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TeamSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('teams')->insert([
            [
                'name' => 'Nankatsu',
                'budget' => 10000,
                'wins' => 0,
                'draws' => 0,
                'losses' => 0,
                'description' => 'Équipe équilibrée et technique, centrée sur le jeu collectif et la créativité offensive. Capable de contrôler le rythme du match et de faire la différence par la maîtrise du ballon.'
            ],
            [
                'name' => 'Toho',
                'budget' => 10000,
                'wins' => 0,
                'draws' => 0,
                'losses' => 0,
                'description' => 'Équipe agressive et directe, axée sur la puissance offensive et le jeu vertical. Pression constante et grande efficacité dans les phases de finition.'
            ],
            [
                'name' => 'Hanawa',
                'budget' => 10000,
                'wins' => 0,
                'draws' => 0,
                'losses' => 0,
                'description' => 'Équipe athlétique et explosive, spécialisée dans les duels et les attaques rapides. Dangereuse dans les phases aériennes mais parfois irrégulière collectivement.'
            ],
            [
                'name' => 'Furano',
                'budget' => 10000,
                'wins' => 0,
                'draws' => 0,
                'losses' => 0,
                'description' => 'Équipe très endurante et disciplinée, basée sur le combat physique et la constance. Difficile à battre sur la durée, elle use l’adversaire progressivement.'
            ],
            [
                'name' => 'Otomo',
                'budget' => 10000,
                'wins' => 0,
                'draws' => 0,
                'losses' => 0,
                'description' => 'Équipe rigoureuse et très organisée, dotée d’une défense dense et disciplinée. Peu de fantaisie mais redoutable en transition et en jeu regroupé.'
            ],
            [
                'name' => 'Azumaichi',
                'budget' => 10000,
                'wins' => 0,
                'draws' => 0,
                'losses' => 0,
                'description' => 'Équipe très dure et défensive, axée sur l’impact physique et les duels. Bloc solide, pressing agressif et volonté constante de casser le jeu adverse.'
            ],
            [
                'name' => 'Musashi',
                'budget' => 10000,
                'wins' => 0,
                'draws' => 0,
                'losses' => 0,
                'description' => 'Équipe extrêmement technique et tactique, privilégiant le jeu posé et intelligent. Capable de dominer par la maîtrise, mais vulnérable face à une forte pression.'
            ],
            [
                'name' => 'Shutetsu',
                'budget' => 10000,
                'wins' => 0,
                'draws' => 0,
                'losses' => 0,
                'description' => 'Équipe solide et bien structurée, réputée pour sa formation rigoureuse. Jeu collectif discipliné, sans véritable star mais difficile à manœuvrer.'
            ],
            [
                'name' => 'Meiwa',
                'budget' => 10000,
                'wins' => 0,
                'draws' => 0,
                'losses' => 0,
                'description' => 'Équipe agressive et collective, spécialisée dans le pressing et l’intensité. Jeu dur, rythme élevé et forte pression mentale sur l’adversaire.'
            ],
            [
                'name' => 'Hirado',
                'budget' => 10000,
                'wins' => 0,
                'draws' => 0,
                'losses' => 0,
                'description' => 'Équipe ultra-physique et intimidante. Défense brutale, jeu direct et affrontement constant, cherchant à gagner par la domination physique.'
            ],
            [
                'name' => 'Naniwa',
                'budget' => 10000,
                'wins' => 0,
                'draws' => 0,
                'losses' => 0,
                'description' => 'Équipe très défensive et méthodique, reposant sur un bloc bas compact et un gardien solide. Cherche à fermer le jeu et punir sur de rares occasions.'
            ],
            [
                'name' => 'Minawi',
                'budget' => 10000,
                'wins' => 0,
                'draws' => 0,
                'losses' => 0,
                'description' => 'Équipe équilibrée sans spécialisation marquée. Jeu simple et appliqué, capable de rivaliser collectivement mais manquant d’impact individuel.'
            ],
            [
                'name' => 'Nakahara',
                'budget' => 10000,
                'wins' => 0,
                'draws' => 0,
                'losses' => 0,
                'description' => 'Équipe modeste et peu spectaculaire, basée sur un jeu simple et structuré. Dépend fortement de rares éléments offensifs pour exister.'
            ],
            [
                'name' => 'Shimizu',
                'budget' => 10000,
                'wins' => 0,
                'draws' => 0,
                'losses' => 0,
                'description' => 'Équipe prudente et moyenne, sans point fort dominant. Organisation correcte mais difficulté à faire la différence individuellement.'
            ],
            [
                'name' => 'Shimada',
                'budget' => 10000,
                'wins' => 0,
                'draws' => 0,
                'losses' => 0,
                'description' => 'Équipe très neutre et disciplinée. Possède un bloc compact, un jeu simple et peu risqué, avec un danger principalement collectif.'
            ],
        ]);
    }
}
