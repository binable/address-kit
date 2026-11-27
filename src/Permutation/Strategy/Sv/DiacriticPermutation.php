<?php

declare(strict_types=1);

namespace Binable\AddressKit\Permutation\Strategy\Sv;

use Binable\AddressKit\Model\Address;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

/**
 * Folds the Swedish letters "å", "ä" and "ö" and puts them back
 * ("Järnvägsgatan" <-> "Jarnvagsgatan", "Malmö" <-> "Malmo").
 *
 * Folding is applied as a whole, exactly like
 * {@see \Binable\AddressKit\Permutation\Strategy\Pl\DiacriticPermutation}: an
 * export either keeps the Swedish letters or loses all of them.
 *
 * The opposite direction is ambiguous - an "a" can stand for "a", "å" and
 * "ä" - so it is not derived but looked up. Swedish street names are
 * compounds, so besides whole words the lookup knows the generic terms at the
 * end of a word ("-vagen" -> "-vägen") and the common first elements in front
 * of it ("Bjork-", "Vaster-"); the part in between is left as it is.
 */
#[AsTaggedItem(priority: 100)]
final class DiacriticPermutation extends AbstractSwedishPermutation
{
    /**
     * Swedish character => its folded spelling. This direction is never
     * ambiguous.
     *
     * @var array<string, string>
     */
    private const TO_ASCII = [
        'å' => 'a', 'ä' => 'a', 'ö' => 'o',
        'Å' => 'A', 'Ä' => 'A', 'Ö' => 'O',
    ];

    /**
     * Folded word => Swedish word, for words that are not built from the
     * parts below.
     *
     * @var array<string, string>
     */
    private const WORDS = [
        // the leading adjectives
        'sodra' => 'södra', 'ostra' => 'östra', 'vastra' => 'västra',
        'ovre' => 'övre', 'langa' => 'långa', 'forsta' => 'första',
        'hoga' => 'höga', 'hogen' => 'högen', 'ang' => 'äng',
        'langgatan' => 'långgatan', 'hogvagen' => 'högvägen',
        // the towns and districts
        'goteborg' => 'göteborg', 'malmo' => 'malmö', 'linkoping' => 'linköping',
        'norrkoping' => 'norrköping', 'jonkoping' => 'jönköping',
        'nykoping' => 'nyköping', 'enkoping' => 'enköping', 'lidkoping' => 'lidköping',
        'koping' => 'köping', 'orebro' => 'örebro', 'vasteras' => 'västerås',
        'umea' => 'umeå', 'lulea' => 'luleå', 'skelleftea' => 'skellefteå',
        'pitea' => 'piteå', 'gavle' => 'gävle', 'boras' => 'borås',
        'sodertalje' => 'södertälje', 'vaxjo' => 'växjö', 'ostersund' => 'östersund',
        'trollhattan' => 'trollhättan', 'angelholm' => 'ängelholm',
        'harnosand' => 'härnösand', 'ornskoldsvik' => 'örnsköldsvik',
        'hassleholm' => 'hässleholm', 'hoganas' => 'höganäs',
        'taby' => 'täby', 'lidingo' => 'lidingö', 'jarfalla' => 'järfälla',
        'sodermalm' => 'södermalm',
        'ostermalm' => 'östermalm', 'alvsjo' => 'älvsjö', 'angby' => 'ängby',
        'vallingby' => 'vällingby', 'malardalen' => 'mälardalen',
    ];

    /**
     * Folded first element => Swedish first element, longest first. Applied
     * repeatedly, so "Vasterlanggatan" becomes "Västerlånggatan".
     *
     * @var array<string, string>
     */
    private const PREFIXES = [
        'jarnvags' => 'järnvägs', 'tradgards' => 'trädgårds', 'prastgards' => 'prästgårds',
        'kopmans' => 'köpmans', 'kopman' => 'köpman', 'vaster' => 'väster',
        'oster' => 'öster', 'soder' => 'söder', 'bjork' => 'björk', 'fagel' => 'fågel',
        'malar' => 'mälar', 'angs' => 'ängs', 'lonn' => 'lönn', 'ronn' => 'rönn',
        'lang' => 'lång', 'sjo' => 'sjö', 'aker' => 'åker', 'hog' => 'hög',
    ];

    /**
     * Folded generic term => Swedish generic term, matched at the end of a
     * word, longest first.
     *
     * @var array<string, string>
     */
    private const SUFFIXES = [
        'granden' => 'gränden', 'straket' => 'stråket', 'slatten' => 'slätten',
        'hojden' => 'höjden', 'garden' => 'gården', 'grand' => 'gränd',
        'vagen' => 'vägen', 'angen' => 'ängen', 'akern' => 'åkern',
        'gard' => 'gård', 'aker' => 'åker', 'vag' => 'väg',
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

        foreach ([strtr($value, self::TO_ASCII), $this->toSwedish($value)] as $variation) {
            if ($variation !== $value) {
                yield $variation;
            }
        }
    }

    private function toSwedish(string $value): string
    {
        return preg_replace_callback(
            '/\p{L}+/u',
            static function (array $match): string {
                $word = $match[0];
                $swedish = self::lookUp(mb_strtolower($word));
                if (null === $swedish) {
                    return $word;
                }

                $first = mb_substr($word, 0, 1);

                return match (true) {
                    mb_strlen($word) > 1 && mb_strtoupper($word) === $word => mb_strtoupper($swedish),
                    mb_strtoupper($first) === $first => self::ucfirst($swedish),
                    default => $swedish,
                };
            },
            $value,
        ) ?? $value;
    }

    private static function lookUp(string $word): ?string
    {
        if (isset(self::WORDS[$word])) {
            return self::WORDS[$word];
        }

        $head = '';
        $rest = $word;
        do {
            $found = false;
            foreach (self::PREFIXES as $folded => $swedish) {
                if (str_starts_with($rest, $folded) && \strlen($rest) > \strlen($folded)) {
                    $head .= $swedish;
                    $rest = substr($rest, \strlen($folded));
                    $found = true;

                    break;
                }
            }
        } while ($found);

        $tail = '';
        foreach (self::SUFFIXES as $folded => $swedish) {
            if (str_ends_with($rest, $folded)) {
                $tail = $swedish;
                $rest = substr($rest, 0, -\strlen($folded));

                break;
            }
        }

        $swedish = $head.$rest.$tail;

        return $swedish === $word ? null : $swedish;
    }
}
