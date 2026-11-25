<?php

declare(strict_types=1);

namespace Binable\AddressKit\Permutation\Strategy\Da;

use Binable\AddressKit\Model\Address;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

/**
 * The leading element of a Danish street name, written out and abbreviated:
 * "Gammel Kongevej" / "Gl. Kongevej", "Sankt Peders Stræde" /
 * "Skt. Peders Stræde" / "Sct. Peders Stræde", "Nørre Farimagsgade" /
 * "Nr. Farimagsgade", "Store Kongensgade" / "St. Kongensgade".
 *
 * These are the only abbreviations Danish street names really carry - the
 * generic term at the end is always written out, it is the element in front
 * that the municipalities shorten. "St." is the ugly one: it stands for
 * "Sankt" and for "Store" alike, so both readings are generated.
 *
 * Only the elements that stand as a word of their own are listed. A glued
 * "Nørrebrogade" is one word and is left alone.
 */
#[AsTaggedItem(priority: 20)]
final class PrefixPermutation extends AbstractDanishPermutation
{
    /**
     * Full spelling => abbreviation. Both directions are generated, several
     * spellings of the same element are listed separately.
     *
     * @var list<array{string, string}>
     */
    private const ABBREVIATIONS = [
        ['gammel', 'gl.'],
        ['gamle', 'gl.'],
        ['gammelt', 'gl.'],
        ['sankt', 'skt.'],
        ['sankt', 'sct.'],
        ['sankt', 'st.'],
        ['store', 'st.'],
        ['lille', 'll.'],
        ['nørre', 'nr.'],
        ['nordre', 'ndr.'],
        ['sønder', 'sdr.'],
        ['søndre', 'sdr.'],
        ['vester', 'v.'],
        ['vestre', 'v.'],
        ['øster', 'ø.'],
        ['østre', 'ø.'],
    ];

    public function permute(Address $address): iterable
    {
        if (null === $address->street) {
            return;
        }

        foreach ($this->getPairVariations($address->street, self::ABBREVIATIONS) as $street) {
            yield $address->withStreet($street);
        }
    }
}
