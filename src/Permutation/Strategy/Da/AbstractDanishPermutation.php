<?php

declare(strict_types=1);

namespace Binable\AddressKit\Permutation\Strategy\Da;

use Binable\AddressKit\Permutation\LocalizedPermutationStrategyInterface;
use Binable\AddressKit\Permutation\Strategy\AbstractPermutation;

/**
 * Base class for permutations that only make sense for Danish street names
 * ("Nørregade", "Gammel Kongevej", "Sankt Peders Stræde").
 *
 * Danish is built like German: the generic term is glued to the end of the
 * name, not put in front of it. What German does not have is the definite
 * article that is glued on top of it - "Skovvej" and "Skovvejen", "Hovedgade"
 * and "Hovedgaden" are the same street written twice, and the register and the
 * street sign regularly disagree on which one it is.
 *
 * Unlike German, Danish does not separate the term from a plain name: "Skov
 * Vej" does not exist. Only behind a name of several words ("Sankt Hans Gade")
 * does it stand apart, see {@see StreetTermPermutation}. What does stand on
 * its own is the leading element ("Gammel Kongevej", "Sankt Peders Stræde"),
 * and that one is abbreviated - {@see PrefixPermutation}.
 */
abstract class AbstractDanishPermutation extends AbstractPermutation implements LocalizedPermutationStrategyInterface
{
    public function getLanguage(): string
    {
        return 'da';
    }
}
