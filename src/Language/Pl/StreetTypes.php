<?php

declare(strict_types=1);

namespace Binable\AddressKit\Language\Pl;

use Binable\AddressKit\Language\AbstractStreetTypes;

/**
 * The generic terms a Polish street name starts with ("ul. Długa",
 * "al. Jana Pawła II", "os. Piastowskie"), with the abbreviations of the gminy
 * and the TERYT register. Polish writes them in lower case.
 *
 * Used by {@see \Binable\AddressKit\Permutation\Strategy\Pl\StreetTypePermutation}.
 */
final class StreetTypes extends AbstractStreetTypes
{
    /**
     * "park", "rynek" and "szlak" are the first word of the name itself
     * ("Rynek Główny", "Park Kościuszki") at least as often as they are a
     * generic term, see {@see self::NAME_LIKE_TYPES}.
     */
    public const TYPES = [
        'wybrzeże' => ['wybrzeże', 'wybrzeze', 'wyb.'],
        'osiedle' => ['osiedle', 'os.', 'os'],
        'bulwar' => ['bulwar', 'bulwary', 'bulw.'],
        'skwer' => ['skwer', 'skw.'],
        'ulica' => ['ulica', 'ul.', 'ul'],
        'aleja' => ['aleja', 'aleje', 'al.', 'al'],
        'pasaż' => ['pasaż', 'pasaz'],
        'plac' => ['plac', 'pl.', 'pl'],
        'rondo' => ['rondo'],
        'szosa' => ['szosa'],
        'droga' => ['droga'],
    ];

    public const NAME_LIKE_TYPES = [
        'park' => ['park'],
        'rynek' => ['rynek'],
        'szlak' => ['szlak'],
    ];
}
