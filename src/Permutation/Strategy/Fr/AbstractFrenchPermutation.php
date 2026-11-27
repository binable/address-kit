<?php

declare(strict_types=1);

namespace Binable\AddressKit\Permutation\Strategy\Fr;

use Binable\AddressKit\Language\Fr\StreetTypes;
use Binable\AddressKit\Permutation\LocalizedPermutationStrategyInterface;
use Binable\AddressKit\Permutation\Strategy\AbstractLeadingTypePermutation;

/**
 * Base class for permutations that only make sense for French street names
 * ("Avenue du Général de Gaulle", "Rue Saint-Étienne", "Bd Voltaire").
 *
 * French street names are built the other way round than German ones: the
 * generic term ("type de voie") comes first and is never glued to the name
 * part, while the name part itself is hyphenated. Both halves are therefore
 * handled by their own permutation - {@see StreetTypePermutation} for the type
 * de voie and {@see HyphenPermutation} for the name.
 *
 * In multilingual countries (BE, CH) the Dutch and German street names stay
 * untouched: every permutation of this namespace needs a French type de voie
 * or a French-only diacritic to fire.
 */
abstract class AbstractFrenchPermutation extends AbstractLeadingTypePermutation implements LocalizedPermutationStrategyInterface
{
    /**
     * @see StreetTypes::TYPES
     */
    protected const TYPES = StreetTypes::TYPES;

    public function getLanguage(): string
    {
        return 'fr';
    }
}
