<?php

declare(strict_types=1);

namespace Binable\AddressKit\Permutation\Strategy\En;

use Binable\AddressKit\Permutation\LocalizedPermutationStrategyInterface;
use Binable\AddressKit\Permutation\Strategy\AbstractPermutation;

/**
 * Base class for permutations of American street names ("W Sunset Blvd",
 * "2nd Ave NE", "Reinhardt Rd East").
 *
 * An American street name is built the way USPS Publication 28 describes it:
 * an optional directional in front, the name, an optional suffix and an
 * optional directional behind it. Every one of those parts is written in full
 * or abbreviated, and a gazetteer that keeps them in columns of their own
 * stores the bare name only - so a street is known under up to nine spellings.
 *
 * The abbreviations are the USPS ones, which is why the default
 * {@see \Binable\AddressKit\Language\StreetLanguageMap} only maps the US to
 * "en": Great Britain, Ireland, New Zealand and Canada write "Rd" and "Ave" as
 * well, but "St" doubles as Saint there and Canada mixes in French.
 */
abstract class AbstractEnglishPermutation extends AbstractPermutation implements LocalizedPermutationStrategyInterface
{
    /**
     * Street suffixes as the USPS abbreviates them, full spelling => abbreviation.
     *
     * @var array<string, string>
     */
    public const SUFFIXES = [
        'Avenue' => 'Ave', 'Boulevard' => 'Blvd', 'Circle' => 'Cir', 'Court' => 'Ct',
        'Drive' => 'Dr', 'Expressway' => 'Expy', 'Freeway' => 'Fwy', 'Highway' => 'Hwy',
        'Lane' => 'Ln', 'Parkway' => 'Pkwy', 'Place' => 'Pl', 'Road' => 'Rd',
        'Square' => 'Sq', 'Street' => 'St', 'Terrace' => 'Ter', 'Trail' => 'Trl', 'Way' => 'Way',
    ];

    /**
     * Directionals as the USPS abbreviates them, full spelling => abbreviation.
     *
     * @var array<string, string>
     */
    public const DIRECTIONALS = [
        'Northeast' => 'NE', 'Northwest' => 'NW', 'Southeast' => 'SE', 'Southwest' => 'SW',
        'North' => 'N', 'South' => 'S', 'East' => 'E', 'West' => 'W',
    ];

    /**
     * Numbered street names as gazetteers spell them out ("Second Ave").
     *
     * @var array<string, string>
     */
    public const ORDINALS = [
        '1st' => 'First', '2nd' => 'Second', '3rd' => 'Third', '4th' => 'Fourth', '5th' => 'Fifth',
        '6th' => 'Sixth', '7th' => 'Seventh', '8th' => 'Eighth', '9th' => 'Ninth', '10th' => 'Tenth',
        '11th' => 'Eleventh', '12th' => 'Twelfth', '13th' => 'Thirteenth', '14th' => 'Fourteenth', '15th' => 'Fifteenth',
        '16th' => 'Sixteenth', '17th' => 'Seventeenth', '18th' => 'Eighteenth', '19th' => 'Nineteenth', '20th' => 'Twentieth',
    ];

    public function getLanguage(): string
    {
        return 'en';
    }

    /**
     * Splits a street into the parts USPS Publication 28 names: directional in
     * front, name, suffix, directional behind ("N Main St E").
     *
     * A part is only taken off while a name is left over: "E St" is the street
     * named E, "West Street" the street named West and "North Ave" the avenue
     * named North - none of them has a directional.
     *
     * @return array{pre: ?string, name: list<string>, suffix: ?string, post: ?string}
     */
    protected function parse(string $street): array
    {
        $words = $this->toWords($street);
        $pre = $suffix = $post = null;

        if (\count($words) >= 3 && null !== $this->findSpelling($words[\count($words) - 1], self::DIRECTIONALS)) {
            $post = array_pop($words);
        }

        if (\count($words) >= 2 && null !== $this->findSpelling($words[\count($words) - 1], self::SUFFIXES)) {
            $suffix = array_pop($words);
        }

        if (\count($words) >= 2 && null !== $this->findSpelling($words[0], self::DIRECTIONALS)) {
            $pre = array_shift($words);
        }

        return ['pre' => $pre, 'name' => $words, 'suffix' => $suffix, 'post' => $post];
    }

    /**
     * @param array{pre: ?string, name: list<string>, suffix: ?string, post: ?string} $parts
     */
    protected function build(array $parts): string
    {
        return implode(' ', array_filter(
            [$parts['pre'], ...$parts['name'], $parts['suffix'], $parts['post']],
            static fn (?string $word): bool => null !== $word && '' !== $word,
        ));
    }

    /**
     * The full spelling and the abbreviation of a word that is one of them, as
     * [full, abbreviation] - or null for any other word. A trailing full stop
     * ("St.", "N.") is ignored.
     *
     * @param array<string, string> $table
     *
     * @return array{string, string}|null
     */
    protected function findSpelling(string $word, array $table): ?array
    {
        $word = mb_strtolower(rtrim($word, '.'));

        foreach ($table as $full => $short) {
            if ($word === mb_strtolower($full) || $word === mb_strtolower($short)) {
                return [$full, $short];
            }
        }

        return null;
    }

    /**
     * @return list<string>
     */
    protected function toWords(string $street): array
    {
        $collapsed = trim(preg_replace('/\s+/u', ' ', $street) ?? $street);

        return '' === $collapsed ? [] : explode(' ', $collapsed);
    }
}
