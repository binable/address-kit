<?php

declare(strict_types=1);

namespace Binable\AddressKit\Permutation\Strategy\Sv;

use Binable\AddressKit\Permutation\LocalizedPermutationStrategyInterface;
use Binable\AddressKit\Permutation\Strategy\AbstractPermutation;

/**
 * Base class for permutations that only make sense for Swedish street names
 * ("Storgatan", "Västra Hamngatan", "Järnvägsgatan").
 *
 * Swedish glues the generic term to the end of the name like Danish and German
 * ("-gatan", "-vägen", "-gränd"); the diacritics are handled by
 * {@see DiacriticPermutation}.
 */
abstract class AbstractSwedishPermutation extends AbstractPermutation implements LocalizedPermutationStrategyInterface
{
    public function getLanguage(): string
    {
        return 'sv';
    }
}
