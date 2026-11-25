<?php

declare(strict_types=1);

namespace Binable\AddressKit\Permutation;

/**
 * A strategy that only makes sense for street names of one language ("Hauptstraße", "Rue de la Paix").
 *
 * The {@see AddressPermutator} only runs it for addresses whose country uses that language for its
 * street names, see {@see \Binable\AddressKit\Language\StreetLanguageMap}. Strategies that do not
 * implement this interface are language independent and run for every address.
 */
interface LocalizedPermutationStrategyInterface extends PermutationStrategyInterface
{
    /**
     * ISO 639-1 code of the language, lowercase (e.g. "de").
     */
    public function getLanguage(): string;
}
