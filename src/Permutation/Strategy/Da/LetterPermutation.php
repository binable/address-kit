<?php

declare(strict_types=1);

namespace Binable\AddressKit\Permutation\Strategy\Da;

use Binable\AddressKit\Model\Address;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

/**
 * The three Danish letters in every spelling a register uses: "Nørregade" /
 * "Noerregade", "Ærøvej" / "Aeroevej", and the one that is not a
 * transliteration at all but an orthography of its own - "Århus" / "Aarhus",
 * "Åbenrå" / "Aabenraa".
 *
 * The "aa" is the Danish counterpart of the German "ß": the 1948 reform
 * replaced it with "å", the municipalities went back to "aa" in their own
 * names, and decades of street registers sit on both sides of that line.
 *
 * Folding is applied as a whole and never rule by rule: a register either
 * stores the Danish spelling or the folded one, it never mixes both within one
 * name.
 */
#[AsTaggedItem(priority: 100)]
final class LetterPermutation extends AbstractDanishPermutation
{
    /**
     * Danish letter => its folded spelling. This direction is never ambiguous.
     *
     * @var array<string, string>
     */
    private const TO_ASCII = [
        'æ' => 'ae', 'ø' => 'oe', 'å' => 'aa',
        'Æ' => 'Ae', 'Ø' => 'Oe', 'Å' => 'Aa',
    ];

    /**
     * The bare "a" spelling some exports fall back to instead of "aa" -
     * "Åboulevard" turns up as "Aboulevard" just as often as "Aaboulevard".
     *
     * @var array<string, string>
     */
    private const TO_BARE_ASCII = [
        'æ' => 'ae', 'ø' => 'o', 'å' => 'a',
        'Æ' => 'Ae', 'Ø' => 'O', 'Å' => 'A',
    ];

    /**
     * Folded spelling => Danish letter. Ambiguous, therefore guarded:
     *
     * - "oe" after a vowel is never an "ø"
     * - "ae" after a vowel and in the classic name endings is never an "æ"
     *   ("Michael", "Israel")
     * - "aa" after a vowel is never an "å"
     *
     * @var array<string, string>
     */
    private const TO_DANISH = [
        '/(?<![aeiouyæøå])ae(?!l\b)/iu' => 'æ',
        '/(?<![aeiouyæøå])oe/iu' => 'ø',
        '/(?<![aeiouyæøå])aa/iu' => 'å',
    ];

    public function permute(Address $address): iterable
    {
        foreach ($this->getVariations($address->street) as $street) {
            yield $address->withStreet($street);
        }

        foreach ($this->getVariations($address->city) as $city) {
            yield $address->withCity($city);
        }
    }

    /**
     * @return iterable<string>
     */
    private function getVariations(?string $value): iterable
    {
        if (null === $value) {
            return;
        }

        $variations = [
            strtr($value, self::TO_ASCII),
            strtr($value, self::TO_BARE_ASCII),
            $this->toDanish($value),
        ];

        foreach (array_unique($variations) as $variation) {
            if ($variation !== $value) {
                yield $variation;
            }
        }
    }

    private function toDanish(string $value): string
    {
        foreach (self::TO_DANISH as $pattern => $letter) {
            $value = preg_replace_callback(
                $pattern,
                static fn (array $match): string => mb_strtoupper(mb_substr($match[0], 0, 1)) === mb_substr($match[0], 0, 1)
                    ? mb_strtoupper($letter)
                    : $letter,
                $value,
            ) ?? $value;
        }

        return $value;
    }
}
