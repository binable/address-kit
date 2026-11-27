<?php

declare(strict_types=1);

namespace Binable\AddressKit\Permutation\Strategy\It;

use Binable\AddressKit\Language\It\StreetTypes;
use Binable\AddressKit\Permutation\LocalizedPermutationStrategyInterface;
use Binable\AddressKit\Permutation\Strategy\AbstractLeadingTypePermutation;

/**
 * Base class for permutations that only make sense for Italian street names
 * ("Via Guglielmo Marconi", "P.zza Sant'Agostino", "C.so Vittorio Emanuele").
 *
 * Italian street names are built like the French ones: the generic term
 * ("tipo di via") comes first, separated by a space and never glued to the
 * name. What is Italian and has no French counterpart is the amount of
 * abbreviations with an inner dot ("V.le", "P.zza", "C.so", "F.lli") and the
 * elision in front of a vowel ("Sant'Agostino").
 *
 * The German street names of South Tyrol and the German and French ones of
 * Switzerland stay untouched: every permutation of this namespace needs an
 * Italian tipo di via to fire.
 */
abstract class AbstractItalianPermutation extends AbstractLeadingTypePermutation implements LocalizedPermutationStrategyInterface
{
    /**
     * @see StreetTypes::TYPES
     */
    protected const TYPES = StreetTypes::TYPES;

    public function getLanguage(): string
    {
        return 'it';
    }

    /**
     * Only the first letter, never every word: the Italian abbreviations carry
     * an inner dot ("P.zza", "V.le", "C.so", "F.lli") and the part behind it is
     * a fragment of the same word, not a second one.
     */
    protected function capitalise(string $value): string
    {
        return self::ucfirst($value);
    }
}
