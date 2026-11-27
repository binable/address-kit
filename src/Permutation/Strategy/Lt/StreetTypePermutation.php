<?php

declare(strict_types=1);

namespace Binable\AddressKit\Permutation\Strategy\Lt;

use Binable\AddressKit\Language\Lt\StreetTypes;
use Binable\AddressKit\Model\Address;
use Binable\AddressKit\Permutation\LocalizedPermutationStrategyInterface;
use Binable\AddressKit\Permutation\Strategy\AbstractPermutation;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

/**
 * All spellings of the generic term that ends a Lithuanian street name, plus
 * none at all and the one that is missing:
 *
 *  - "Vilniaus g." / "Vilniaus gatvė" / "Vilniaus gatve" / "Vilniaus"
 *  - "Gedimino pr." / "Gedimino prospektas" / "Gedimino"
 *  - "Vilniaus" / "Vilniaus g."
 *
 * The address register of the Registrų centras keeps the name and the
 * abbreviated term in columns of their own, so the bare name is what it
 * stores. A street written without a term is far more often a "gatvė" than
 * anything else; only that one is added.
 */
#[AsTaggedItem(priority: 10)]
final class StreetTypePermutation extends AbstractPermutation implements LocalizedPermutationStrategyInterface
{
    private const IMPLIED_TYPE = 'g.';

    public function getLanguage(): string
    {
        return 'lt';
    }

    public function permute(Address $address): iterable
    {
        $street = $address->street;
        if (null === $street) {
            return;
        }

        $words = preg_split('/\s+/u', trim($street), -1, \PREG_SPLIT_NO_EMPTY) ?: [];
        if ([] === $words) {
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
        $last = array_pop($words);

        if ([] === $words || !StreetTypes::isType($last)) {
            if (!StreetTypes::isType($last, true) && 1 === preg_match('/\p{L}/u', $last)) {
                yield implode(' ', [...$words, $last, self::IMPLIED_TYPE]);
            }

            return;
        }

        $name = implode(' ', $words);

        foreach (StreetTypes::getSpellings($last) as $spelling) {
            yield $name.' '.$this->matchCase($spelling, $last);
        }

        yield $name;
    }
}
