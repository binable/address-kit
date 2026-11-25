<?php

declare(strict_types=1);

namespace Binable\AddressKit\Permutation\Strategy;

use Binable\AddressKit\Model\Address;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

/**
 * The house number typed into the street field: "Denkstr. 19", "Via Roma 12/B",
 * "Langgata 1 D".
 *
 * A user who writes the whole line into the street field leaves the registers
 * with a street they do not know - every register stores the name without the
 * number. The number is therefore split off and the bare name tried as well.
 *
 * Both readings of the number stay in play: first the house number the user
 * put into its own field, then the one that was stuck to the street. They are
 * usually the same ("Denkstr. 19" plus "19"), and the duplicate is dropped by
 * the {@see \Binable\AddressKit\Permutation\AddressPermutator}.
 *
 * Language independent: no register writes the number into the name. What
 * looks like one but is not - a name whose last word is a number
 * ("Chaussee 1", "Kilometer 5") - keeps its original spelling in front, so a
 * register that knows it still answers.
 *
 * Runs first so the bare name is fed through the case, generic term and hyphen
 * variants as well.
 */
#[AsTaggedItem(priority: 300)]
final class HouseNumberInStreetPermutation extends AbstractPermutation
{
    /**
     * A trailing house number: digits, optionally followed by a letter or by a
     * second number ("19", "19a", "19 a", "12/B", "33-35"). The name in front
     * has to end on a letter or on the dot of an abbreviation ("Denkstr. 19"),
     * so a name that is only a number is left alone.
     */
    private const TRAILING_NUMBER = '/^(.*\p{L}\.?)[\s,]+(\d+\s*(?:[-\/]\s*[\da-zA-Z]{1,4})?\s*[a-zA-Z]?)$/u';

    public function permute(Address $address): iterable
    {
        if (null === $address->street || 1 !== preg_match(self::TRAILING_NUMBER, trim($address->street), $matches)) {
            return;
        }

        $street = trim(preg_replace('/\s+/u', ' ', $matches[1]) ?? $matches[1]);
        $number = preg_replace('/\s+/u', '', $matches[2]) ?? $matches[2];

        // A name that is nothing but a generic term plus the number ("Am 7")
        // is not split.
        if (mb_strlen($street) < 3) {
            return;
        }

        yield $address->withStreet($street);

        if ('' !== $number && $number !== $address->houseNumber && mb_strlen($number) <= 10) {
            yield $address->withStreet($street)->withHouseNumber($number);
        }
    }
}
