<?php

declare(strict_types=1);

namespace Binable\AddressKit\Permutation\Strategy\De;

use Binable\AddressKit\Model\Address;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

/**
 * Transliterates umlauts and ß in both directions ("Müllerweg" <-> "Muellerweg").
 *
 * Both directions are applied as a whole and never rule by rule: a register
 * either stores the German spelling or the transliterated one, it never mixes
 * both within one name. That keeps the number of permutations at two per field
 * and still covers names with several umlauts ("Müllergäßchen" ->
 * "Muellergaesschen").
 */
#[AsTaggedItem(priority: 100)]
final class UmlautPermutation extends AbstractGermanPermutation
{
    /**
     * German spelling => transliteration. This direction is never ambiguous.
     *
     * @var array<string, string>
     */
    private const TO_ASCII = [
        'ä' => 'ae',
        'ö' => 'oe',
        'ü' => 'ue',
        'Ä' => 'Ae',
        'Ö' => 'Oe',
        'Ü' => 'Ue',
        'ß' => 'ss',
        'ẞ' => 'SS',
    ];

    /**
     * Transliteration => German spelling. Ambiguous, therefore guarded:
     *
     * - "ue" after a vowel or q is never an ü ("Aue", "Neue", "Querweg"), and
     *   neither is one at the end of a word - German has no "-ue" ending, but
     *   the French speaking part of Switzerland is full of them ("Rue de la
     *   Paix", "Avenue de la Gare")
     * - "ae" after a vowel and in the classic name endings is never an ä
     *   ("Michael", "Raphael", "Israel")
     * - "oe" after a vowel and in "Goethe" is never an ö
     *
     * @var array<string, string>
     */
    private const TO_UMLAUT = [
        '/(?<![aeiouäöü])(?<!ch)(?<!ph)(?<!sr)ae/iu' => 'ä',
        '/(?<![aeiouäöü])oe(?!the)/iu' => 'ö',
        '/(?<![aeiouäöüq])ue(?=\p{L})/iu' => 'ü',
    ];

    /**
     * "ss" between two vowels is only turned into "ß" for stems where ß is
     * orthographically possible (the "ss" in front of a consonant or at the end
     * of a word is handled by rule in {@see toGerman()}) - the naive replacement
     * turns "Kirchgasse" into "Kirchgaße" and "Kissinger Straße" into
     * "Kißinger Straße". Most entries are the pre-1996 spelling that municipal
     * street registers still use ("Schloßstraße").
     *
     * @var array<string, string>
     */
    private const TO_SHARP_S = [
        'schloss' => 'schloß',
        'gross' => 'groß',
        'ross' => 'roß',
        'nuss' => 'nuß',
        'fuss' => 'fuß',
        'gruss' => 'gruß',
        'fluss' => 'fluß',
        'fass' => 'faß',
        'weiss' => 'weiß',
        'heiss' => 'heiß',
        'meiss' => 'meiß',
        'giess' => 'gieß',
        'stoss' => 'stoß',
        'floss' => 'floß',
        'kloss' => 'kloß',
        'preuss' => 'preuß',
        'neuss' => 'neuß',
        'reuss' => 'reuß',
        'voss' => 'voß',
        'gaesschen' => 'gäßchen',
        'gässchen' => 'gäßchen',
    ];

    public function permute(Address $address): iterable
    {
        foreach ($this->getVariations($address->street, true) as $street) {
            yield $address->withStreet($street);
        }

        foreach ($this->getVariations($address->city, false) as $city) {
            yield $address->withCity($city);
        }
    }

    /**
     * @param bool $keepGenericTerm keep a trailing "straße"/"str." untouched -
     *                              {@see StrassePermutation} owns those
     *                              spellings and runs later in the chain, so
     *                              transliterating them here would only double
     *                              the tree
     *
     * @return iterable<string>
     */
    private function getVariations(?string $value, bool $keepGenericTerm): iterable
    {
        if (null === $value) {
            return;
        }

        $term = '';
        if ($keepGenericTerm && 1 === preg_match('/(?:stra(?:ss|ß)e|str\.?)$/iu', $value, $matches)) {
            $term = $matches[0];
            $value = mb_substr($value, 0, -mb_strlen($term));
        }

        foreach ([$this->toAscii($value), $this->toGerman($value)] as $variation) {
            if ($variation !== $value) {
                yield $variation.$term;
            }
        }
    }

    private function toAscii(string $value): string
    {
        return strtr($value, self::TO_ASCII);
    }

    private function toGerman(string $value): string
    {
        $isUpper = static fn (string $match): bool => mb_strtoupper(mb_substr($match, 0, 1)) === mb_substr($match, 0, 1);

        foreach (self::TO_UMLAUT as $pattern => $umlaut) {
            $value = preg_replace_callback(
                $pattern,
                static fn (array $match): string => $isUpper($match[0]) ? mb_strtoupper($umlaut) : $umlaut,
                $value,
            ) ?? $value;
        }

        foreach (self::TO_SHARP_S as $stem => $replacement) {
            $value = preg_replace_callback(
                '/'.preg_quote($stem, '/').'/iu',
                static fn (array $match): string => $isUpper($match[0]) ? self::ucfirst($replacement) : $replacement,
                $value,
            ) ?? $value;
        }

        // Pre-1996 orthography, still kept by many street registers: "ss" in
        // front of a consonant or at the end of the word was always written "ß"
        // ("Hülspaßweg", "Paßstraße"), while "ss" between two vowels stays
        // ("Kirchgasse", "Kissinger Straße").
        return preg_replace('/ss(?![aeiouyäöü])/iu', 'ß', $value) ?? $value;
    }
}
