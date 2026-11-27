<?php

declare(strict_types=1);

namespace Binable\AddressKit\Permutation\Strategy\Cs;

use Binable\AddressKit\Language\Cs\StreetTypes;
use Binable\AddressKit\Model\Address;
use Binable\AddressKit\Permutation\LocalizedPermutationStrategyInterface;
use Binable\AddressKit\Permutation\Strategy\AbstractPermutation;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

/**
 * All spellings of the generic term of a Czech street name, wherever it
 * stands: "nám. Míru" / "náměstí Míru" / "namesti Míru", "Národní tř." /
 * "Národní třída".
 *
 * A leading "ulice" is dropped instead: RÚIAN never writes it, while users put
 * it in front of every other street ("ul. Masarykova" -> "Masarykova").
 *
 * The term keeps the case it was written in - lower case in front of a name,
 * as Czech spells it, capitalised where the input capitalised it.
 */
#[AsTaggedItem(priority: 10)]
final class StreetTypePermutation extends AbstractPermutation implements LocalizedPermutationStrategyInterface
{
    public function getLanguage(): string
    {
        return 'cs';
    }

    public function permute(Address $address): iterable
    {
        $street = $address->street;
        if (null === $street) {
            return;
        }

        $words = preg_split('/\s+/u', trim($street), -1, \PREG_SPLIT_NO_EMPTY) ?: [];
        if (\count($words) < 2) {
            return;
        }

        foreach ($this->getVariations($words) as $variation) {
            if ($variation !== $street) {
                yield $address->withStreet($variation);
            }
        }
    }

    /**
     * @param non-empty-list<string> $words
     *
     * @return iterable<string>
     */
    private function getVariations(array $words): iterable
    {
        $first = $words[0];
        if ('ulice' === StreetTypes::getCanonical($first)) {
            yield implode(' ', \array_slice($words, 1));

            return;
        }

        $position = StreetTypes::isType($first) ? 0 : \count($words) - 1;
        $found = $words[$position];
        if (!StreetTypes::isType($found)) {
            return;
        }

        foreach (StreetTypes::getSpellings($found) as $spelling) {
            $words[$position] = $this->matchCase($spelling, $found);
            yield implode(' ', $words);
        }
    }
}
