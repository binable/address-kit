<?php

declare(strict_types=1);

namespace Binable\AddressKit\Permutation\Strategy\Pl;

use Binable\AddressKit\Language\Pl\StreetTypes;
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
     * @see StreetTypes::TYPES
     */
    protected const TYPES = StreetTypes::TYPES;

    public function getLanguage(): string
    {
        return 'pl';
    }
}
