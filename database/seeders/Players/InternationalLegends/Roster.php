<?php

namespace Database\Seeders\Players\InternationalLegends;

use Database\Seeders\Players\InternationalLegends\Support\WorldLegendProfiles;
use Illuminate\Support\Str;
use Database\Seeders\Players\InternationalLegends\Countries\Belgium;

/** Assemble les légendes par pays. Les nouveaux profils sont ajoutés après le catalogue initial. */
final class Roster
{
    public const COUNTRIES = [
        Countries\Germany::class,
        Countries\France::class,
        Countries\Argentina::class,
        Countries\Netherlands::class,
        Countries\Uruguay::class,
        Countries\Mexico::class,
        Countries\Brazil::class,
        Countries\Italy::class,
        Countries\Portugal::class,
        Countries\England::class,
        Countries\Spain::class,
        Countries\Croatia::class,
        Countries\Denmark::class,
        Countries\Paraguay::class,
        Countries\CzechRepublic::class,
        Countries\Ireland::class,
        Countries\Romania::class,
        Countries\Japan::class,
        Countries\Ukraine::class,
        Countries\Bulgaria::class,
        Countries\Liberia::class,
        Countries\UnitedStates::class,
        Countries\Hungary::class,
        Countries\Poland::class,
        Countries\Morocco::class,
        Countries\Sweden::class,
        Countries\Slovenia::class,
        Countries\CostaRica::class,
        Countries\IvoryCoast::class,
        Countries\Serbia::class,
        Countries\CongoDR::class,
        Countries\Canada::class,
        Countries\Norway::class,
        Countries\Peru::class,
        Countries\Nigeria::class,
        Countries\Australia::class,
        Countries\Albania::class,
        Countries\Guinea::class,
        Countries\Finland::class,
        Countries\Yugoslavia::class,
        Countries\Wales::class,
        Countries\TrinidadTobago::class,
        Countries\SouthKorea::class,
        Countries\SaudiArabia::class,
        Countries\China::class,
        Countries\Senegal::class,
        Countries\Cameroon::class,
    ];

    public static function players(): array
    {
        $players = [];
        foreach (self::COUNTRIES as $country) {
            array_push($players, ...WorldLegendProfiles::players($country::ROSTER));
        }
        array_push($players, ...Belgium::players());
        foreach (self::COUNTRIES as $country) {
            array_push($players, ...WorldLegendProfiles::players($country::ADDITIONS));
        }
        foreach (self::COUNTRIES as $country) {
            array_push($players, ...WorldLegendProfiles::players($country::HISTORICAL_ADDITIONS ?? []));
        }
        array_push($players, ...WorldLegendProfiles::players(Belgium::HISTORICAL_ADDITIONS ?? []));
        foreach (self::COUNTRIES as $country) {
            if (defined($country.'::EXTRA_ADDITIONS')) {
                array_push($players, ...WorldLegendProfiles::players($country::EXTRA_ADDITIONS));
            }
        }
        return $players;
    }

    public static function nationalities(): array
    {
        $map = [];
        foreach (self::COUNTRIES as $country) {
            $extras = defined($country.'::EXTRA_ADDITIONS') ? $country::EXTRA_ADDITIONS : [];
            foreach (array_merge($country::ROSTER, $country::ADDITIONS, $country::HISTORICAL_ADDITIONS ?? [], $extras) as $entry) {
                $map[Str::slug($entry['firstname'].' '.$entry['lastname'])] = $country::NATIONALITY;
            }
        }
        return array_merge($map, Belgium::nationalities());
    }
}
