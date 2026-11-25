<?php

declare(strict_types=1);

namespace Binable\AddressKit\Permutation\Strategy\Da;

use Binable\AddressKit\Model\Address;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

/**
 * The Latin diacritics a Danish register drops and puts back: "Allé" / "Alle",
 * "Bülowsvej" / "Bulowsvej", "Café Vej" / "Cafe Vej".
 *
 * "æ", "ø" and "å" are deliberately not touched here - they are letters of the
 * alphabet, not accents, and they fold to "ae"/"oe"/"aa" rather than to a bare
 * vowel. {@see LetterPermutation} owns them; folding them a second time here
 * would only produce a spelling no register has ever stored.
 *
 * What is left over are the accents Danish carries on loan words and on the
 * German names of Sønderjylland. Only one of them is common enough to be worth
 * the opposite direction as well: the acute on "allé", which the address
 * registers store both ways within the same municipality.
 *
 * Folding is applied as a whole and never rule by rule: a register either
 * stores the accented spelling or the folded one, it never mixes both within
 * one name.
 */
#[AsTaggedItem(priority: 90)]
final class AccentPermutation extends AbstractDanishPermutation
{
    /**
     * Accented character => its folded spelling. This direction is never
     * ambiguous. "æ", "ø" and "å" are absent on purpose.
     *
     * @var array<string, string>
     */
    private const TO_ASCII = [
        'á' => 'a', 'à' => 'a', 'â' => 'a', 'ä' => 'a', 'ã' => 'a',
        'ç' => 'c',
        'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
        'í' => 'i', 'ì' => 'i', 'î' => 'i', 'ï' => 'i',
        'ñ' => 'n',
        'ó' => 'o', 'ò' => 'o', 'ô' => 'o', 'ö' => 'o', 'õ' => 'o',
        'ú' => 'u', 'ù' => 'u', 'û' => 'u', 'ü' => 'u',
        'ý' => 'y', 'ÿ' => 'y',
        'Á' => 'A', 'À' => 'A', 'Â' => 'A', 'Ä' => 'A', 'Ã' => 'A',
        'Ç' => 'C',
        'É' => 'E', 'È' => 'E', 'Ê' => 'E', 'Ë' => 'E',
        'Í' => 'I', 'Ì' => 'I', 'Î' => 'I', 'Ï' => 'I',
        'Ñ' => 'N',
        'Ó' => 'O', 'Ò' => 'O', 'Ô' => 'O', 'Ö' => 'O', 'Õ' => 'O',
        'Ú' => 'U', 'Ù' => 'U', 'Û' => 'U', 'Ü' => 'U',
        'Ý' => 'Y', 'Ÿ' => 'Y',
    ];

    /**
     * Folded word => accented word. "Allé" standing on its own is the only
     * accented word Danish street names really carry; the glued spellings
     * ("Rosenallé") are the business of {@see StreetTermPermutation}, which
     * knows them as one of the generic terms.
     *
     * @var array<string, string>
     */
    private const TO_ACCENT = [
        'alle' => 'allé',
        'alleen' => 'alléen',
        'alleer' => 'alléer',
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

        foreach (array_unique([strtr($value, self::TO_ASCII), $this->toAccented($value)]) as $variation) {
            if ($variation !== $value) {
                yield $variation;
            }
        }
    }

    private function toAccented(string $value): string
    {
        return preg_replace_callback(
            '/\p{L}+/u',
            static function (array $match): string {
                $accented = self::TO_ACCENT[mb_strtolower($match[0])] ?? null;
                if (null === $accented) {
                    return $match[0];
                }

                return mb_strtoupper(mb_substr($match[0], 0, 1)) === mb_substr($match[0], 0, 1)
                    ? self::ucfirst($accented)
                    : $accented;
            },
            $value,
        ) ?? $value;
    }
}
