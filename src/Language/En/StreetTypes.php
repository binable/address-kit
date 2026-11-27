<?php

declare(strict_types=1);

namespace Binable\AddressKit\Language\En;

use Binable\AddressKit\Language\AbstractStreetTypes;

/**
 * The street types that end an English street name outside the US ("Maple
 * Close", "Victoria Cres", "Marine Pde"), with the abbreviations of Royal Mail,
 * AS/NZS 4819 (Australia, New Zealand) and the South African registers.
 *
 * Several abbreviations per type are common here ("Cres" / "Cr", "Tce" /
 * "Terr"), so unlike {@see UspsAbbreviations} every type lists all of them.
 * A trailing "St" always stands for Street; "St" in front of a name is Saint
 * and never looked up.
 *
 * Used by the permutations of {@see \Binable\AddressKit\Permutation\Strategy\En}
 * for every country but the US.
 */
final class StreetTypes extends AbstractStreetTypes
{
    public const TYPES = [
        'approach' => ['approach', 'app'],
        'avenue' => ['avenue', 'ave', 'av'],
        'boulevard' => ['boulevard', 'blvd', 'bvd'],
        'circle' => ['circle', 'cir'],
        'circuit' => ['circuit', 'cct'],
        'close' => ['close', 'cl'],
        'court' => ['court', 'ct', 'crt'],
        'crescent' => ['crescent', 'cres', 'cr'],
        'drive' => ['drive', 'dr'],
        'esplanade' => ['esplanade', 'esp'],
        'expressway' => ['expressway', 'expy'],
        'freeway' => ['freeway', 'fwy'],
        'gardens' => ['gardens', 'gdns'],
        'grove' => ['grove', 'gr', 'gve'],
        'highway' => ['highway', 'hwy'],
        'lane' => ['lane', 'ln', 'la'],
        'mews' => ['mews'],
        'parade' => ['parade', 'pde'],
        'parkway' => ['parkway', 'pkwy', 'pky'],
        'place' => ['place', 'pl'],
        'road' => ['road', 'rd'],
        'square' => ['square', 'sq'],
        'street' => ['street', 'st', 'str'],
        'terrace' => ['terrace', 'tce', 'terr', 'ter'],
        'trail' => ['trail', 'trl'],
        'walk' => ['walk', 'wlk'],
        'way' => ['way', 'wy'],
        'wynd' => ['wynd'],
    ];

    /**
     * Just as often the last word of the name itself: "Notting Hill",
     * "Victoria Park", "Bethnal Green", "Sea View".
     */
    public const NAME_LIKE_TYPES = [
        'chase' => ['chase'],
        'circus' => ['circus'],
        'corner' => ['corner', 'cnr'],
        'green' => ['green', 'gn'],
        'heights' => ['heights', 'hts'],
        'hill' => ['hill', 'hl'],
        'link' => ['link'],
        'mall' => ['mall'],
        'park' => ['park', 'pk'],
        'quay' => ['quay', 'qy'],
        'rise' => ['rise'],
        'row' => ['row'],
        'view' => ['view', 'vw'],
    ];
}
