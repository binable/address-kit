<?php

declare(strict_types=1);

namespace Binable\AddressKit\Permutation\Strategy\It;

use Binable\AddressKit\Model\Address;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

/**
 * Strips the Italian accents and puts them back ("Via della Libertà" <->
 * "Via della Liberta"), swaps the typographic apostrophe for the plain one
 * ("Sant’Agostino" <-> "Sant'Agostino") and covers the spelling Italian
 * registers fall back to when they cannot store an accent: the trailing
 * apostrophe ("VIA DELLA LIBERTA'", "CITTA' STUDI").
 *
 * That last one is the Italian counterpart of the German "ae/oe/ue" fallback -
 * a comune that exports its street register in upper case writes every final
 * accent as an apostrophe, and the address the resident types never matches.
 *
 * Stripping is applied as a whole and never rule by rule, exactly like
 * {@see \Binable\AddressKit\Permutation\Strategy\De\UmlautPermutation}: a
 * register either stores the accented spelling or the folded one, it never
 * mixes both within one name. The opposite direction is ambiguous - a final
 * "e" can stand for "è" and for "é" - so both are emitted, and inside a word
 * the accent is not derived but looked up in a list of the words Italian
 * street names are built from.
 *
 * "ä", "ö" and "ü" are deliberately not folded: they are German, not Italian,
 * and South Tyrol is full of them.
 */
#[AsTaggedItem(priority: 100)]
final class AccentPermutation extends AbstractItalianPermutation
{
    /**
     * Accented character => its folded spelling. This direction is never
     * ambiguous.
     *
     * @var array<string, string>
     */
    private const TO_ASCII = [
        'à' => 'a', 'á' => 'a',
        'è' => 'e', 'é' => 'e',
        'ì' => 'i', 'í' => 'i', 'î' => 'i',
        'ò' => 'o', 'ó' => 'o',
        'ù' => 'u', 'ú' => 'u',
        'À' => 'A', 'Á' => 'A',
        'È' => 'E', 'É' => 'E',
        'Ì' => 'I', 'Í' => 'I', 'Î' => 'I',
        'Ò' => 'O', 'Ó' => 'O',
        'Ù' => 'U', 'Ú' => 'U',
    ];

    /**
     * Accented character => the apostrophe spelling of a register that cannot
     * store it. Only vowels that actually end an Italian word carry one.
     *
     * @var array<string, string>
     */
    private const TO_APOSTROPHE = [
        'à' => "a'", 'è' => "e'", 'é' => "e'", 'ì' => "i'", 'ò' => "o'", 'ù' => "u'",
        'À' => "A'", 'È' => "E'", 'É' => "E'", 'Ì' => "I'", 'Ò' => "O'", 'Ù' => "U'",
    ];

    /**
     * A vowel with an apostrophe at the end of a word, and the accent it
     * stands for. "e" is ambiguous and handled separately, everything else has
     * exactly one grave accent.
     *
     * @var array<string, string>
     */
    private const FROM_APOSTROPHE = ['a' => 'à', 'i' => 'ì', 'o' => 'ò', 'u' => 'ù'];

    /**
     * The elision keeps its apostrophe: "Sant'Agostino" and "dell'Orto" are
     * followed by a letter, a dropped accent never is.
     */
    private const FINAL_APOSTROPHE = '/([aeiouAEIOU])[\'\x{2019}](?![\p{L}\d])/u';

    /**
     * Folded word => accented word. Only words that carry an accent in every
     * Italian street name they appear in are listed; "roma", "garibaldi" and
     * "marconi" have none and would only bloat the lookup.
     *
     * @var array<string, string>
     */
    private const TO_ACCENT = [
        // generic vocabulary
        'citta' => 'città', 'universita' => 'università', 'liberta' => 'libertà',
        'unita' => 'unità', 'trinita' => 'trinità', 'sanita' => 'sanità',
        'verita' => 'verità', 'pieta' => 'pietà', 'carita' => 'carità',
        'felicita' => 'felicità', 'solidarieta' => 'solidarietà', 'volonta' => 'volontà',
        'localita' => 'località',
        'gioventu' => 'gioventù', 'virtu' => 'virtù', 'servitu' => 'servitù',
        'schiavitu' => 'schiavitù', 'tribu' => 'tribù', 'caffe' => 'caffè',
        // names
        'niccolo' => 'niccolò', 'nicolo' => 'nicolò', 'gesu' => 'gesù',
        'mose' => 'mosè', 'noe' => 'noè', 'giosue' => 'giosuè',
        // places that turn up as a street or as the city itself
        'forli' => 'forlì', 'cefalu' => 'cefalù', 'canicatti' => 'canicattì',
    ];

    /**
     * Typographic apostrophe => plain one. Both directions are generated.
     *
     * @var array<string, string>
     */
    private const APOSTROPHES = ['’' => "'", '‘' => "'", '´' => "'", '`' => "'"];

    public function permute(Address $address): iterable
    {
        // In Switzerland the French strategies already fold the very same
        // vowels, so a Romandie street would only gain duplicates and an
        // Italian trailing apostrophe no Swiss register ever stores. Ticino is
        // told apart by its tipo di via.
        if ('CH' === strtoupper((string) $address->country) && null === $this->splitType($address->street)) {
            return;
        }

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

        $variations = [];

        // Start from the accented spelling as well, so an address typed
        // without accents reaches the apostrophe form too.
        foreach ([$value, $this->toAccented($value), ...$this->fromApostrophe($value)] as $candidate) {
            $variations[] = $candidate;
            $variations[] = strtr($candidate, self::TO_ASCII);
            $variations[] = strtr($candidate, self::TO_APOSTROPHE);
            $variations[] = strtr($candidate, self::APOSTROPHES);
            $variations[] = str_replace("'", '’', $candidate);
        }

        foreach (array_unique($variations) as $variation) {
            if ($variation !== $value) {
                yield $variation;
            }
        }
    }

    /**
     * "Liberta'" -> "Libertà", "Citta' Studi" -> "Città Studi". A final "e'"
     * yields both accents, so "Caffe'" reaches "Caffè" and "Perche'" "Perché".
     *
     * @return list<string>
     */
    private function fromApostrophe(string $value): array
    {
        $results = [];

        foreach (['è', 'é'] as $accentedE) {
            $table = self::FROM_APOSTROPHE + ['e' => $accentedE];

            $results[] = preg_replace_callback(
                self::FINAL_APOSTROPHE,
                static function (array $match) use ($table): string {
                    $accented = $table[mb_strtolower($match[1])];

                    return $match[1] === mb_strtoupper($match[1]) ? mb_strtoupper($accented) : $accented;
                },
                $value,
            ) ?? $value;
        }

        return array_values(array_filter(array_unique($results), static fn (string $result): bool => $result !== $value));
    }

    private function toAccented(string $value): string
    {
        return preg_replace_callback(
            // A word whose accent is already written as a trailing
            // apostrophe is left to fromApostrophe(), otherwise
            // "LIBERTA'" would end up as "Libertà'".
            '/\p{L}+(?!\p{L})(?![\'\x{2019}](?![\p{L}\d]))/u',
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
