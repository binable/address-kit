<?php

declare(strict_types=1);

namespace Binable\AddressKit\Permutation\Strategy\Da;

use Binable\AddressKit\Model\Address;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

/**
 * The village high street, which the register writes as two words behind
 * the village name: "Lemming Bygade", "Hjerk Bygade" - and which users glue
 * the way Danish glues every other street ("Lemmingbygade").
 *
 * {@see StreetTermPermutation} never splits a plain name, because "Nørre
 * Gade" does not exist. "Bygade" is the exception: it is a term of its own
 * ("by" + "gade"), and what is in front of it is the place's name. Only this
 * term is split; "…skolevej" or "…hovedgade" also occur in both spellings,
 * but are just as often one word.
 *
 * The definite form ("Lemming Bygaden") is handled the same way. A bare
 * "Bygade" has no village in front of it and is left alone.
 */
#[AsTaggedItem(priority: 12)]
final class BygadePermutation extends AbstractDanishPermutation
{
    public function permute(Address $address): iterable
    {
        $street = $address->street;
        if (null === $street) {
            return;
        }

        // "Lemming Bygade" -> "Lemmingbygade"
        if (1 === preg_match('/^(.*\p{L}) (bygaden?)$/iu', $street, $matches)) {
            $term = mb_strtoupper($matches[2]) === $matches[2] ? $matches[2] : mb_strtolower($matches[2]);

            yield $address->withStreet($matches[1].$term);

            return;
        }

        // "Lemmingbygade" -> "Lemming Bygade". Two letters at least in front,
        // a village name is never shorter.
        if (1 === preg_match('/^(.*\p{L}{2})(bygaden?)$/iu', $street, $matches)) {
            $term = mb_strtoupper($matches[2]) === $matches[2] ? $matches[2] : self::ucfirst(mb_strtolower($matches[2]));

            yield $address->withStreet($matches[1].' '.$term);
        }
    }
}
