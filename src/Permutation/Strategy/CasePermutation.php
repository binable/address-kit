<?php

declare(strict_types=1);

namespace Binable\AddressKit\Permutation\Strategy;

use Binable\AddressKit\Model\Address;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

/**
 * Capitalisation as the registers spell a name. Addresses are typed by hand, so
 * a street arrives as "Im hollen", "lagerhausstraße" or "HAUPTSTRASSE" while
 * registers compare their labels literally - the lookup then fails on a street
 * the register knows perfectly well.
 *
 * Language independent: every register capitalises the words a street or place
 * name is built from. What it does not capitalise are the particles in between
 * ("Rue des Bleuets", "Straße im Moos"), the generic term an abbreviation puts
 * in front of the name ("ul. Długa") and the Dutch article in front of a place
 * ("'s-Hertogenbosch"), so those are left as they are typed.
 *
 * Runs before the language specific strategies so the repaired spelling is fed
 * through the umlaut, generic term and hyphen variants as well.
 */
#[AsTaggedItem(priority: 200)]
final class CasePermutation extends AbstractPermutation
{
    /**
     * The words the registers of the covered languages write in lower case
     * inside a name: articles, prepositions and the conjunctions between two
     * name parts.
     *
     * @var list<string>
     */
    private const PARTICLES = [
        // German / Dutch
        'am', 'an', 'auf', 'aus', 'bei', 'bij', 'das', 'de', 'dem', 'den', 'der',
        'des', 'die', 'het', 'im', 'in', 'ob', 'op', 'ter', 'unter', 'van', 'vom',
        'von', 'vor', 'zu', 'zum', 'zur',
        // French
        'au', 'aux', 'd', 'du', 'en', 'et', 'la', 'le', 'les', 'sous', 'sur',
        // Italian
        'ai', 'al', 'alla', 'da', 'dal', 'dei', 'del', 'della', 'di', 'e', 'lo',
        // Polish, incl. the abbreviated generic term in front of the name
        'do', 'i', 'na', 'os', 'pl', 'ul', 'w', 'za',
        // Danish
        'ved',
        // The Dutch article of a place name ("'s-Hertogenbosch", "'t Zand")
        's', 't',
    ];

    public function permute(Address $address): iterable
    {
        // The original spelling stays in play for both parts: usually only one
        // of the two is mistyped.
        $streets = [$address->street, ...$this->getCaseVariations($address->street)];
        $cities = [$address->city, ...$this->getCaseVariations($address->city)];

        foreach ($streets as $street) {
            foreach ($cities as $city) {
                if ($street === $address->street && $city === $address->city) {
                    continue;
                }

                yield $address->withStreet($street)->withCity($city);
            }
        }
    }

    /**
     * The capitalised spelling of a value that is not capitalised itself, or
     * nothing when it already reads the way a register writes it.
     *
     * @return list<string>
     */
    private function getCaseVariations(?string $value): array
    {
        if (null === $value || '' === trim($value)) {
            return [];
        }

        $variation = $this->capitaliseWords($value, $this->isShouted($value));

        return $variation === $value ? [] : [$variation];
    }

    /**
     * A value typed in capitals throughout ("IM HOLLEN"). It carries no
     * capitalisation worth keeping, so every word may be rewritten - including
     * the first one, which is otherwise left alone.
     */
    private function isShouted(string $value): bool
    {
        return $value === mb_strtoupper($value) && $value !== mb_strtolower($value);
    }

    private function capitaliseWords(string $value, bool $shouted): string
    {
        $first = true;

        return preg_replace_callback(
            '/(?<![\p{L}\d])[\p{L}\d]+/u',
            static function (array $match) use (&$first, $shouted): string {
                $word = $match[0];
                $isFirst = $first;
                $first = false;

                // A shouted value is folded down first, otherwise the word
                // keeps every capital the user typed inside it ("McDonald").
                $target = $shouted ? mb_strtolower($word) : $word;

                // A shouted value has no capitalisation left to read, so a
                // name starting on a word that is a particle elsewhere
                // ("IM HOLLEN") would lose its capital to the rule.
                if (\in_array(mb_strtolower($word), self::PARTICLES, true) && !($shouted && $isFirst)) {
                    return $target;
                }

                return mb_strtoupper(mb_substr($target, 0, 1)).mb_substr($target, 1);
            },
            $value,
        ) ?? $value;
    }
}
