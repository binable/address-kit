<?php

declare(strict_types=1);

namespace Binable\AddressKit\Permutation\Strategy\It;

use Binable\AddressKit\Model\Address;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

/**
 * The saints and titles inside the name part, in every spelling the comuni
 * use: "Piazza Sant'Agostino" / "Piazza S. Agostino", "Via San Giovanni" /
 * "Via S. Giovanni", "Via Fratelli Cervi" / "Via F.lli Cervi", "Viale
 * Generale Cadorna" / "Viale Gen. Cadorna".
 *
 * The saints need their own mechanism: unlike a title they are not a word that
 * is simply abbreviated but a marker that elides in front of a vowel
 * ("Sant'Anna"), changes with the gender ("San" / "Santa") and has a plural
 * ("Santi" / "SS."). Which of them is right cannot be derived from the name
 * behind it, so all of them are generated - a wrong one costs one dead lookup,
 * a missing one costs the address.
 */
#[AsTaggedItem(priority: 20)]
final class TitlePermutation extends AbstractItalianPermutation
{
    /**
     * Every saint marker in one alternation, longest first so "santissima"
     * wins over "santi" and "s.ta" over "s.". The name behind it has to start
     * with a capital, otherwise "Via Sandro Pertini" would lose its "San".
     * That first letter is captured as well - the elided forms need to know
     * whether a vowel follows.
     *
     * Caseless only for the marker itself: under a global /i, \p{Lu} matches
     * lower case letters as well and "Sandro" splits into "San dro".
     */
    private const SAINT_MARKER = "/(?<![\\p{L}\\d])((?i)sant['\u{2019}]|santissim[ao]|sant[aoi]|san|ss\\.|s\\.t[ao]|s\\.) ?(?=(\\p{Lu}))/u";

    /**
     * Marker in its canonical spelling => every spelling it may be written
     * with. The trailing space is part of the replacement, the elided forms
     * carry none.
     *
     * @var array<string, list<string>>
     */
    private const SAINT_FORMS = [
        "sant'" => ["Sant'", "Sant\u{2019}", 'Sant ', 'S. '],
        'santissima' => ['Santissima ', 'SS.ma ', 'S. '],
        'santissimo' => ['Santissimo ', 'SS.mo ', 'S. '],
        'santi' => ['Santi ', 'SS. '],
        'santa' => ['Santa ', 'S. ', 'S.ta ', "Sant'", "Sant\u{2019}"],
        'santo' => ['Santo ', 'S. ', 'S.to ', "Sant'", "Sant\u{2019}"],
        'san' => ['San ', 'S. ', "Sant'", "Sant\u{2019}"],
        'ss.' => ['Santi ', 'SS. '],
        's.ta' => ['Santa ', 'S. ', 'S.ta '],
        's.to' => ['Santo ', 'S. ', 'S.to '],
        's.' => ['S. ', 'San ', 'Santa ', 'Santo ', "Sant'", "Sant\u{2019}"],
    ];

    /**
     * The elision only happens in front of a vowel: "Sant'Agostino" exists,
     * "Sant'Marco" does not.
     *
     * @var list<string>
     */
    private const VOWELS = ['A', 'E', 'I', 'O', 'U'];

    /**
     * Full spelling => abbreviation. Both directions are generated, several
     * abbreviations of the same word are listed separately.
     *
     * "P." (padre) and "D." (don) are missing on purpose: in a street name a
     * single letter is an initial ("Via G. Marconi") far more often than a
     * title, and "P." collides with piazza on top of that.
     *
     * @var list<array{string, string}>
     */
    private const ABBREVIATIONS = [
        ['fratelli', 'f.lli'],
        ['fratelli', 'flli'],
        ['monsignor', 'mons.'],
        ['monsignore', 'mons.'],
        ['professor', 'prof.'],
        ['professore', 'prof.'],
        ['ingegner', 'ing.'],
        ['ingegnere', 'ing.'],
        ['dottor', 'dott.'],
        ['dottore', 'dott.'],
        ['dottor', 'dr.'],
        ['avvocato', 'avv.'],
        ['generale', 'gen.'],
        ['colonnello', 'col.'],
        ['capitano', 'cap.'],
        ['maggiore', 'magg.'],
        ['tenente', 'ten.'],
        ['cavaliere', 'cav.'],
        ['onorevole', 'on.'],
        ['presidente', 'pres.'],
        ['martiri', 'mart.'],
        ['madonna', 'mad.'],
    ];

    public function permute(Address $address): iterable
    {
        // Without a tipo di via in front there is no name part, so a German
        // street of South Tyrol and a German or French one of CH stay
        // untouched.
        $street = $address->street;
        if (null === $street || null === $this->splitType($street)) {
            return;
        }

        $variations = [
            ...$this->getSaintVariations($street),
            ...$this->getPairVariations($street, self::ABBREVIATIONS),
        ];

        foreach (array_unique($variations) as $variation) {
            if ($variation !== $street) {
                yield $address->withStreet($variation);
            }
        }
    }

    /**
     * @return list<string>
     */
    private function getSaintVariations(string $street): array
    {
        if (1 !== preg_match(self::SAINT_MARKER, $street, $matches, \PREG_OFFSET_CAPTURE)) {
            return [];
        }

        [$markerText, $markerOffset] = $matches[1];
        $found = str_replace("\u{2019}", "'", mb_strtolower($markerText));
        $followedByVowel = \in_array(mb_strtoupper($matches[2][0]), self::VOWELS, true);

        // Only the first marker is swapped: its byte offset and length, so the
        // forms go in literally (no "$1" expansion as with a regex replace).
        // The marker's optional trailing space belongs to the match as well.
        $length = \strlen($matches[0][0]);

        $variations = [];
        foreach (self::SAINT_FORMS[$found] ?? [] as $form) {
            if (!$followedByVowel && (str_ends_with($form, "'") || str_ends_with($form, "\u{2019}"))) {
                continue;
            }

            $variations[] = substr_replace($street, $form, $markerOffset, $length);
        }

        return $variations;
    }
}
