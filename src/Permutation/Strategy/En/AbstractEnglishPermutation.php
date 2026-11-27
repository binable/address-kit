<?php

declare(strict_types=1);

namespace Binable\AddressKit\Permutation\Strategy\En;

use Binable\AddressKit\Language\En\Ordinals;
use Binable\AddressKit\Language\En\StreetTypes;
use Binable\AddressKit\Language\En\UspsAbbreviations;
use Binable\AddressKit\Model\Address;
use Binable\AddressKit\Permutation\LocalizedPermutationStrategyInterface;
use Binable\AddressKit\Permutation\Strategy\AbstractPermutation;

/**
 * Base class for permutations of English street names ("W Sunset Blvd",
 * "2nd Ave NE", "Maple Cres").
 *
 * An American street name is built the way USPS Publication 28 describes it:
 * an optional directional in front, the name, an optional suffix and an
 * optional directional behind it. Every one of those parts is written in full
 * or abbreviated, and a gazetteer that keeps them in columns of their own
 * stores the bare name only - so a street is known under up to nine spellings.
 *
 * Outside the US the street type is abbreviated differently ("Cres", "Tce",
 * "Pde") and with more than one spelling per type, see
 * {@see \Binable\AddressKit\Language\En\StreetTypes}. Directionals are an
 * American convention: in "East Kilbride Road" the "East" is part of the name,
 * so they are only split off for US addresses.
 *
 * Which table applies is decided by the country of the address: the US and an
 * address without a country use USPS, every other country the
 * Commonwealth table.
 */
abstract class AbstractEnglishPermutation extends AbstractPermutation implements LocalizedPermutationStrategyInterface
{
    /**
     * @deprecated use {@see UspsAbbreviations::SUFFIXES}
     *
     * @var array<string, string>
     */
    public const SUFFIXES = UspsAbbreviations::SUFFIXES;

    /**
     * @deprecated use {@see UspsAbbreviations::DIRECTIONALS}
     *
     * @var array<string, string>
     */
    public const DIRECTIONALS = UspsAbbreviations::DIRECTIONALS;

    /**
     * @deprecated use {@see Ordinals::SPELLED_OUT}
     *
     * @var array<string, string>
     */
    public const ORDINALS = Ordinals::SPELLED_OUT;

    public function getLanguage(): string
    {
        return 'en';
    }

    /**
     * True for an address that follows USPS Publication 28: one in the US or
     * one without a country.
     */
    protected function isUsps(Address $address): bool
    {
        return null === $address->country || 'US' === strtoupper(trim($address->country));
    }

    /**
     * Every street type with all of its spellings, full one first.
     *
     * @return array<string, list<string>>
     */
    protected function getSuffixes(Address $address): array
    {
        return $this->isUsps($address) ? self::toSpellings(UspsAbbreviations::SUFFIXES) : StreetTypes::TYPES;
    }

    /**
     * Every directional with all of its spellings - none outside the US.
     *
     * @return array<string, list<string>>
     */
    protected function getDirectionals(Address $address): array
    {
        return $this->isUsps($address) ? self::toSpellings(UspsAbbreviations::DIRECTIONALS) : [];
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
    protected function parse(Address $address, string $street): array
    {
        $suffixes = $this->getSuffixes($address);
        $directionals = $this->getDirectionals($address);

        $words = $this->toWords($street);
        $pre = $suffix = $post = null;

        if (\count($words) >= 3 && null !== $this->findSpellings($words[\count($words) - 1], $directionals)) {
            $post = array_pop($words);
        }

        if (\count($words) >= 2 && null !== $this->findSpellings($words[\count($words) - 1], $suffixes)) {
            $suffix = array_pop($words);
        }

        if (\count($words) >= 2 && null !== $this->findSpellings($words[0], $directionals)) {
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
     * Every spelling of a word that is one of the table, in the case of the
     * street it stands in ("BLVD" -> "BOULEVARD", "cres" -> "Crescent") - or
     * null for any other word. A trailing full stop ("St.", "N.") is ignored.
     *
     * @param array<string, list<string>> $table
     *
     * @return list<string>|null
     */
    protected function findSpellings(string $word, array $table, bool $upperCase = false): ?array
    {
        $needle = mb_strtolower(rtrim($word, '.'));

        foreach ($table as $spellings) {
            foreach ($spellings as $spelling) {
                if ($needle === mb_strtolower($spelling)) {
                    return array_map(
                        fn (string $spelling): string => match (true) {
                            $upperCase => mb_strtoupper($spelling),
                            $spelling === mb_strtolower($spelling) => $this->capitalise($spelling),
                            default => $spelling,
                        },
                        $spellings,
                    );
                }
            }
        }

        return null;
    }

    protected function isUpperCase(string $street): bool
    {
        return $street === mb_strtoupper($street) && $street !== mb_strtolower($street);
    }

    /**
     * @return list<string>
     */
    protected function toWords(string $street): array
    {
        $collapsed = trim(preg_replace('/\s+/u', ' ', $street) ?? $street);

        return '' === $collapsed ? [] : explode(' ', $collapsed);
    }

    /**
     * @param array<string, string> $abbreviations full spelling => abbreviation
     *
     * @return array<string, list<string>>
     */
    private static function toSpellings(array $abbreviations): array
    {
        $spellings = [];
        foreach ($abbreviations as $full => $short) {
            $spellings[$full] = array_values(array_unique([$full, $short]));
        }

        return $spellings;
    }
}
