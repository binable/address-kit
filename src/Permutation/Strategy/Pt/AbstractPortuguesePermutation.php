<?php

declare(strict_types=1);

namespace Binable\AddressKit\Permutation\Strategy\Pt;

use Binable\AddressKit\Language\Pt\StreetTypes;
use Binable\AddressKit\Permutation\LocalizedPermutationStrategyInterface;
use Binable\AddressKit\Permutation\Strategy\AbstractLeadingTypePermutation;

/**
 * Base class for permutations that only make sense for Portuguese street names
 * ("Rua da Liberdade", "Av. da República", "Pç. do Comércio").
 *
 * Portuguese street names are built like the French ones: the generic term
 * ("tipo de via") comes first, separated by a space, and the name behind it is
 * joined by a lower case particle ("da", "do", "das", "dos").
 */
abstract class AbstractPortuguesePermutation extends AbstractLeadingTypePermutation implements LocalizedPermutationStrategyInterface
{
    /**
     * @see StreetTypes::TYPES
     */
    protected const TYPES = StreetTypes::TYPES;

    public function getLanguage(): string
    {
        return 'pt';
    }

    /**
     * Only the first letter: "b.º" must not turn into "B.º" with a capital
     * ordinal indicator, and "pç." has no second word.
     */
    protected function capitalise(string $value): string
    {
        return self::ucfirst($value);
    }
}
