<?php

namespace Database\Seeders\Support;

/**
 * Éligibilité des joueurs à la sélection nationale. Seuls les personnages Captain Tsubasa de clubs japonais
 * sont filtrés : 42 de premier plan restent éligibles, les figurants des effectifs sont exclus.
 * Les autres joueurs (légendes réelles, internationaux, autres univers) sont toujours éligibles.
 */
final class NationalPool
{
    /** Personnages japonais de clubs Captain Tsubasa éligibles à la sélection du Japon. */
    public const JAPAN_TSUBASA_ELIGIBLE = [
            'Tsubasa Ozora',
            'Taro Misaki',
            'Yuzo Morisaki',
            'Ryo Ishizaki',
            'Genzo Wakabayashi',
            'Mamoru Izawa',
            'Hajime Taki',
            'Teppei Kisugi',
            'Shingo Takasugi',
            'Ken Wakashimazu',
            'Kojiro Hyuga',
            'Takeshi Sawada',
            'Tsuneo Takashima',
            'Kazuki Sorimachi',
            'Masao Tachibana',
            'Kazuo Tachibana',
            'Yuichiro Daimaru',
            'Hanji Urabe',
            'Shun Nitta',
            'Koji Nishio',
            'Masao Nakayama',
            'Takeshi Kishida',
            'Shinishi Sakamoto',
            'Motoharu Nagano',
            'Yuichi Suenaga',
            'Hikaru Matsuyama',
            'Kazumasa Oda',
            'Masanori Kato',
            'Jun Misugi',
            'Minoru Honma',
            'Hiroshi Jito',
            'Mitsuru Sano',
            'Makoto Soda',
            'Tetsuo Ishida',
            'Shingo Aoi',
            'Taichi Nakanishi',
            'Junichi Nagasaki',
            'Kazumasa Kato',
            'Michel Yamada',
            'Nobuyuki Yumikura',
            'Takashi Sugimoto',
            'Toshiya Okano',
    ];

    /** Vrai si le joueur peut être convoqué en sélection nationale. */
    public static function eligible(string $origin, ?string $nationality, string $fullName): bool
    {
        if ($origin === 'captain_tsubasa' && $nationality === 'Japon') {
            return in_array($fullName, self::JAPAN_TSUBASA_ELIGIBLE, true);
        }

        return true;
    }
}
