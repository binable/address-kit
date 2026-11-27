<?php

declare(strict_types=1);

namespace Binable\AddressKit\Permutation\Strategy\Ro;

use Binable\AddressKit\Language\Ro\StreetTypes;
use Binable\AddressKit\Permutation\LocalizedPermutationStrategyInterface;
use Binable\AddressKit\Permutation\Strategy\AbstractLeadingTypePermutation;

/**
 * Base class for permutations that only make sense for Romanian street names
 * ("Str. Mihai Eminescu", "B-dul Unirii", "Calea Victoriei").
 *
 * The generic term comes first, in its articulated form ("strada", not
 * "stradă"), and is abbreviated with a dot or a hyphen ("Bd.", "B-dul").
 */
abstract class AbstractRomanianPermutation extends AbstractLeadingTypePermutation implements LocalizedPermutationStrategyInterface
{
    /**
     * @see StreetTypes::TYPES
     */
    protected const TYPES = StreetTypes::TYPES;

    public function getLanguage(): string
    {
        return 'ro';
    }

    /**
     * Only the first letter: the part behind the hyphen of "B-dul" and "P-ța"
     * is a fragment of the same word, not a second one.
     */
    protected function capitalise(string $value): string
    {
        return self::ucfirst($value);
    }
}
