<?php

declare(strict_types=1);

namespace Binable\AddressKit\Permutation\Strategy\Pl;

use Binable\AddressKit\Permutation\LocalizedPermutationStrategyInterface;
use Binable\AddressKit\Permutation\Strategy\AbstractLeadingTypePermutation;

/**
 * Base class for permutations that only make sense for Polish street names
 * ("ul. Tadeusza Kościuszki", "al. Jana Pawła II", "os. Piastowskie").
 *
 * Polish street names put the generic term in front like the French and
 * Italian ones, but with two twists of their own:
 *
 *  - the term is written in lower case ("ul. Długa", never "Ul. Długa") and is
 *    left out just as often as it is written
 *  - the name behind it stands in the genitive and carries the given name of
 *    the person it honours, which half the registers drop
 *
 * Both are handled by {@see StreetTypePermutation} and
 * {@see GivenNamePermutation}; the diacritics are handled by
 * {@see DiacriticPermutation}.
 */
abstract class AbstractPolishPermutation extends AbstractLeadingTypePermutation implements LocalizedPermutationStrategyInterface
{
    /**
     * Every spelling of a generic term. First entry is the full one, the
     * others are the abbreviations the gminy and the TERYT register use.
     *
     * "park", "rynek" and "szlak" are left out on purpose: they are the first
     * word of the name itself ("Rynek Główny", "Park Kościuszki") at least as
     * often as they are a generic term, and permuting those only produces dead
     * lookups.
     *
     * @var array<string, list<string>>
     */
    protected const TYPES = [
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

    public function getLanguage(): string
    {
        return 'pl';
    }
}
