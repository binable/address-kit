<?php

declare(strict_types=1);

namespace Binable\AddressKit\Permutation\Strategy\En;

use Binable\AddressKit\Model\Address;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

/**
 * Numbered streets written in figures and spelled out: "2nd Ave" / "Second
 * Ave". Some city gazetteers only know the spelled out form, the address
 * layers of most counties only the figures.
 */
#[AsTaggedItem(priority: 30)]
final class OrdinalPermutation extends AbstractEnglishPermutation
{
    public function permute(Address $address): iterable
    {
        if (null === $address->street) {
            return;
        }

        $spelled = array_change_key_case(self::ORDINALS);
        $figures = array_change_key_case(array_flip(self::ORDINALS));

        $original = $this->toWords($address->street);
        $words = [];
        foreach ($original as $word) {
            $key = mb_strtolower($word);
            $words[] = $spelled[$key] ?? $figures[$key] ?? $word;
        }

        $street = implode(' ', $words);
        if ($street !== implode(' ', $original)) {
            yield $address->withStreet($street);
        }
    }
}
