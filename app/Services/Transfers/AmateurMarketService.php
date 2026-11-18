<?php

namespace App\Services\Transfers;

use App\Contracts\Randomizer;
use App\Enums\Nationality;
use App\Models\GameSaves\GamePlayer;
use App\Models\GameSaves\GameSave;
use App\Services\Players\MoraleService;

/**
 * Filet de sécurité du marché des transferts : garantit en permanence un petit
 * contingent d'« amateurs » libres (origin = amateur), nettement plus faibles
 * que les joueurs de la base (stats 8-13 contre 15+ ; salaire 8-15 contre 22+).
 *
 * But : qu'un club à court d'effectif ou d'argent puisse toujours compléter son
 * onze, sans rendre le jeu plus facile — les bons joueurs restent rares et
 * disputés avec l'IA, qui n'engage d'amateur qu'en cas d'urgence (< 11 joueurs).
 */
class AmateurMarketService
{

    /** Reçoit le générateur aléatoire (noms, statistiques et coût des amateurs). */
    public function __construct(private Randomizer $random)
    {
    }
    /** Nombre minimum d'amateurs libres par poste. */
    public const FLOOR_BY_POSITION = [
        'Goalkeeper' => 1,
        'Defender'   => 2,
        'Midfielder' => 2,
        'Forward'    => 2,
    ];

    /** Noms fictifs regroupés par nationalité pour éviter les mélanges entre pays. */
    private const NAME_POOLS = [
        Nationality::JAPON => [
            'firstnames' => ['Akio', 'Daichi', 'Eiji', 'Fumio', 'Goro', 'Haruki', 'Isamu', 'Jiro', 'Kazuki', 'Kenta', 'Makoto', 'Naoki', 'Osamu', 'Riku', 'Satoru', 'Shinji', 'Takumi', 'Tetsuo', 'Yuji', 'Yusuke'],
            'lastnames' => ['Abe', 'Endo', 'Fujita', 'Goto', 'Hayashi', 'Ikeda', 'Kato', 'Kimura', 'Kobayashi', 'Maeda', 'Mori', 'Murata', 'Nakano', 'Ogawa', 'Okada', 'Saito', 'Sakamoto', 'Shimizu', 'Ueno', 'Yamada'],
        ],
        Nationality::BELGIQUE => [
            'firstnames' => ['Arnaud', 'Bastien', 'Florian', 'Jules', 'Lars', 'Milan', 'Niels', 'Wout'],
            'lastnames' => ['Lambert', 'Dubois', 'Leclercq', 'Simon', 'Peeters', 'Claes', 'Maes', 'Willems'],
        ],
        Nationality::FRANCE => [
            'firstnames' => ['Mathieu', 'Antoine', 'Benoît', 'Clément', 'Hugo', 'Loïc', 'Quentin', 'Rémi'],
            'lastnames' => ['Moreau', 'Bernard', 'Chevalier', 'Garnier', 'Laurent', 'Lefèvre', 'Mercier', 'Perrin'],
        ],
        Nationality::ALLEMAGNE => [
            'firstnames' => ['Felix', 'Jonas', 'Lukas', 'Moritz', 'Niklas', 'Philipp', 'Timo', 'Tobias'],
            'lastnames' => ['Bauer', 'Becker', 'Fischer', 'Hoffmann', 'Keller', 'Koch', 'Schneider', 'Weiss'],
        ],
        Nationality::ANGLETERRE => [
            'firstnames' => ['Callum', 'Ethan', 'Harry', 'Jack', 'Lewis', 'Oliver', 'Samuel', 'Thomas'],
            'lastnames' => ['Bennett', 'Brooks', 'Cooper', 'Foster', 'Hughes', 'Parker', 'Turner', 'Wood'],
        ],
        Nationality::ESPAGNE => [
            'firstnames' => ['Álvaro', 'Diego', 'Hugo', 'Iván', 'Javier', 'Marcos', 'Pablo', 'Sergio'],
            'lastnames' => ['Cabrera', 'Castillo', 'Delgado', 'García', 'Medina', 'Ortega', 'Romero', 'Vega'],
        ],
        Nationality::ITALIE => [
            'firstnames' => ['Alessio', 'Andrea', 'Davide', 'Fabio', 'Luca', 'Matteo', 'Niccolò', 'Simone'],
            'lastnames' => ['Bianchi', 'Conti', 'De Luca', 'Ferrara', 'Galli', 'Lombardi', 'Marino', 'Rossi'],
        ],
        Nationality::PORTUGAL => [
            'firstnames' => ['André', 'Diogo', 'Duarte', 'Gonçalo', 'João', 'Miguel', 'Rui', 'Tiago'],
            'lastnames' => ['Barbosa', 'Cardoso', 'Correia', 'Fernandes', 'Lopes', 'Martins', 'Ribeiro', 'Sousa'],
        ],
        Nationality::PAYS_BAS => [
            'firstnames' => ['Bram', 'Daan', 'Jesse', 'Joris', 'Koen', 'Luuk', 'Sander', 'Thijs'],
            'lastnames' => ['Bakker', 'Bos', 'De Vries', 'Jansen', 'Mulder', 'Smit', 'Van Dijk', 'Visser'],
        ],
        Nationality::BRESIL => [
            'firstnames' => ['Caio', 'Danilo', 'Felipe', 'Gustavo', 'Henrique', 'Leandro', 'Rafael', 'Vinícius'],
            'lastnames' => ['Almeida', 'Barros', 'Carvalho', 'Costa', 'Ferreira', 'Mendes', 'Pereira', 'Santos'],
        ],
        Nationality::ARGENTINE => [
            'firstnames' => ['Agustín', 'Bruno', 'Facundo', 'Gonzalo', 'Joaquín', 'Lautaro', 'Matías', 'Tomás'],
            'lastnames' => ['Acosta', 'Benítez', 'Cabrera', 'Domínguez', 'Giménez', 'Molina', 'Rojas', 'Sosa'],
        ],
        Nationality::MAROC => [
            'firstnames' => ['Amine', 'Ayoub', 'Hamza', 'Ilyas', 'Mehdi', 'Nabil', 'Walid', 'Yassine'],
            'lastnames' => ['Alaoui', 'Amrani', 'Bennani', 'Berrada', 'El Fassi', 'Idrissi', 'Lahlou', 'Mansouri'],
        ],
        Nationality::NIGERIA => [
            'firstnames' => ['Chidi', 'Chinedu', 'Emeka', 'Femi', 'Ibrahim', 'Kelechi', 'Seyi', 'Tunde'],
            'lastnames' => ['Adeyemi', 'Afolabi', 'Bello', 'Eze', 'Nwosu', 'Obi', 'Okafor', 'Okeke'],
        ],
        Nationality::GHANA => [
            'firstnames' => ['Kofi', 'Kwame', 'Kwesi', 'Kojo', 'Yaw', 'Kwaku', 'Nana', 'Akwasi'],
            'lastnames' => ['Adjei', 'Agyeman', 'Asante', 'Boateng', 'Mensah', 'Ofori', 'Opoku', 'Owusu'],
        ],
        Nationality::COREE_DU_SUD => [
            'firstnames' => ['Min-jun', 'Seo-jun', 'Ji-ho', 'Do-yun', 'Hyun-woo', 'Jun-seo', 'Si-woo', 'Tae-hyun'],
            'lastnames' => ['Han', 'Jung', 'Kang', 'Kim', 'Lee', 'Lim', 'Park', 'Yoon'],
        ],
        Nationality::CHINE => [
            'firstnames' => ['Wei', 'Jun', 'Tao', 'Lei', 'Hao', 'Ming', 'Jian', 'Peng'],
            'lastnames' => ['Chen', 'Huang', 'Li', 'Liu', 'Wang', 'Wu', 'Yang', 'Zhang'],
        ],
        Nationality::ARABIE_SAOUDITE => [
            'firstnames' => ['Omar', 'Abdullah', 'Fahad', 'Faisal', 'Khalid', 'Nasser', 'Saad', 'Yasir'],
            'lastnames' => ['Al-Harbi', 'Al-Ghamdi', 'Al-Qahtani', 'Al-Shammari', 'Al-Dosari', 'Al-Otaibi', 'Al-Zahrani', 'Al-Shehri'],
        ],
    ];

    /**
     * Complète le marché jusqu'au plancher par poste. Retourne le nombre d'amateurs créés.
     */
    public function ensureFloor(GameSave $gameSave): int
    {
        // Coupe du Monde : pas de marché des transferts.
        if ($gameSave->isWorldCup()) return 0;

        $week = (int) ($gameSave->week ?? 1);

        $freeByPosition = GamePlayer::where('game_save_id', $gameSave->id)
            ->where('origin', GamePlayer::ORIGIN_AMATEUR)
            ->whereDoesntHave('contracts', fn($q) => $q->where('game_save_id', $gameSave->id)->activeAt($week))
            ->get()
            ->countBy('position');

        $created = 0;
        foreach (self::FLOOR_BY_POSITION as $position => $floor) {
            for ($i = (int) ($freeByPosition[$position] ?? 0); $i < $floor; $i++) {
                $this->generate($gameSave, $position);
                $created++;
            }
        }

        return $created;
    }

    /** Crée en base un joueur amateur libre au poste donné : nom et nationalité cohérents tirés au hasard, stats du poste entre 10 et 13, autres stats entre 6 et 10, coût de 8 à 15, moral neutre. */
    public function generate(GameSave $gameSave, string $position): GamePlayer
    {
        $low  = fn() => $this->random->int(6, 10);   // stats hors poste
        $main = fn() => $this->random->int(10, 13);  // stats du poste (la base démarre à 15)

        $isGK  = $position === 'Goalkeeper';
        $isDef = $position === 'Defender';
        $isMid = $position === 'Midfielder';
        $isAtt = $position === 'Forward';

        $nationality = $this->random->pick(array_keys(self::NAME_POOLS));
        $names = self::NAME_POOLS[$nationality];

        return GamePlayer::create([
            'game_save_id'        => $gameSave->id,
            'firstname'           => $this->random->pick($names['firstnames']),
            'lastname'            => $this->random->pick($names['lastnames']),
            'position'            => $position,
            'origin'              => GamePlayer::ORIGIN_AMATEUR,
            'nationality'         => $nationality,
            'secondary_positions' => [],
            'description'         => 'Joueur amateur du coin : peu coûteux, de quoi compléter un effectif.',
            'speed'               => $gameSave->config()->initialSpeed($this->random->int(25, 40)),
            'stamina'             => $gameSave->getConfig('initial_stamina_random') ? $this->random->int(55, 75) : 100,
            'luck'                => $gameSave->config()->rollLuck(),
            'attack'              => ($isAtt || $isMid) ? $main() : $low(),
            'defense'             => ($isDef || $isGK) ? $main() : $low(),
            'shot'                => $isAtt ? $main() : $low(),
            'pass'                => $isMid ? $main() : $low(),
            'dribble'             => ($isAtt || $isMid) ? $main() : $low(),
            'block'               => $isDef ? $main() : $low(),
            'intercept'           => ($isDef || $isMid) ? $main() : $low(),
            'tackle'              => $isDef ? $main() : $low(),
            'heading'             => $low(),
            'hand_save'           => $isGK ? $main() : 0,
            'punch_save'          => $isGK ? $main() : 0,
            'special_moves'       => [],
            'cost'                => $this->random->int(8, 15),
            'morale'              => MoraleService::NEUTRAL_MORALE,
        ]);
    }
}
