<?php

declare(strict_types=1);

namespace Binable\AddressKit\Permutation\Strategy\Fr;

use Binable\AddressKit\Model\Address;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

/**
 * The titles and saints inside the name part, in both spellings:
 * "Rue Saint-Étienne" / "Rue St-Étienne", "Avenue du Général Leclerc" /
 * "Avenue du Gal Leclerc", "Place du Docteur Roux" / "Place du Dr Roux".
 *
 * The separator is left untouched, so both the hyphenated and the spaced name
 * keep their form - {@see HyphenPermutation} runs before this one and has
 * already produced the other one.
 */
#[AsTaggedItem(priority: 20)]
final class TitlePermutation extends AbstractFrenchPermutation
{
    /**
     * Full spelling => abbreviation. Both directions are generated, several
     * abbreviations of the same word are listed separately.
     *
     * "M." (monsieur) is missing on purpose: in a street name it is an initial
     * ("Rue M. Berthelot") far more often than a title.
     *
     * @var list<array{string, string}>
     */
    private const ABBREVIATIONS = [
        ['saint', 'st'],
        ['saint', 'st.'],
        ['sainte', 'ste'],
        ['sainte', 'ste.'],
        ['notre-dame', 'nd'],
        ['docteur', 'dr'],
        ['docteur', 'dr.'],
        ['général', 'gal'],
        ['général', 'gén.'],
        ['maréchal', 'mal'],
        ['maréchal', 'mar.'],
        ['président', 'pdt'],
        ['président', 'prés.'],
        ['monseigneur', 'mgr'],
        ['colonel', 'col.'],
        ['commandant', 'cdt'],
        ['capitaine', 'cne'],
        ['lieutenant', 'lt'],
        ['professeur', 'prof.'],
        ['madame', 'mme'],
        ['mademoiselle', 'mlle'],
        ['frères', 'frs'],
    ];

    public function permute(Address $address): iterable
    {
        // Without a type de voie in front there is no name part, so a German
        // or Dutch street of BE/CH is never touched.
        if (null === $address->street || null === $this->splitType($address->street)) {
            return;
        }

        foreach ($this->getPairVariations($address->street, self::ABBREVIATIONS) as $street) {
            yield $address->withStreet($street);
        }
    }
}
