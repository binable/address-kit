<?php

declare(strict_types=1);

namespace Binable\AddressKit\Permutation\Strategy\Da;

use Binable\AddressKit\Model\Address;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

/**
 * The linking "s" in front of "vej": "Guldregnsvej" / "Guldregnvej",
 * "Syrensvej" / "Syrenvej".
 *
 * Danish compounds take the "s" by ear, and users drop or add it just as
 * often as the register disagrees with the street sign. Only "vej" (and its
 * definite form "vejen") is handled, by far the most common term, to keep the
 * chained permutations from multiplying the lookups.
 *
 * The "s" is only added behind a consonant: after a vowel Danish does not
 * link with it ("Kirkevej", never "Kirkesvej"). It is only dropped when a
 * consonant stands in front of it; behind a vowel it belongs to the name
 * ("Krusvej" from "Krus") and stays.
 *
 * Only a glued term is touched - "Sankt Hans Vej" has no joint to link.
 */
#[AsTaggedItem(priority: 15)]
final class LinkingSPermutation extends AbstractDanishPermutation
{
    /**
     * A consonant that may carry the linking "s". Sibilants are left out: the
     * "s" is swallowed behind them ("Krussvej" is not a spelling anyone uses).
     */
    private const CONSONANT = '[bcdfghjklmnpqrtvw]';

    public function permute(Address $address): iterable
    {
        $street = $address->street;
        if (null === $street) {
            return;
        }

        // Name with a consonant + "s" in front of the term: drop the "s".
        // At least one more letter has to stand in front, otherwise there is
        // no compound at all.
        if (1 === preg_match('/^(.*\p{L}'.self::CONSONANT.')s(vej(?:en)?)$/iu', $street, $matches)) {
            yield $address->withStreet($matches[1].$matches[2]);

            return;
        }

        // Name ending in a consonant glued to the term: add the "s".
        if (1 === preg_match('/^(.*\p{L}'.self::CONSONANT.')(vej(?:en)?)$/iu', $street, $matches)) {
            $s = mb_strtoupper($matches[2]) === $matches[2] ? 'S' : 's';

            yield $address->withStreet($matches[1].$s.$matches[2]);
        }
    }
}
