<?php

declare(strict_types=1);

namespace Binable\AddressKit\Model;

/**
 * A house number split into its parts.
 *
 *   "12"       → number 12
 *   "12a"      → number 12, addition "a" ("12 a", "12A" and "12 A" alike, the spelling is kept)
 *   "12/3"     → number 12, addition "3"
 *   "16 bis"   → number 16, addition "bis"
 *   "12-14"    → number 12, range end 14
 *   "12-14a"   → number 12, range end 14, addition "a"
 *   "12-3"     → number 12, addition "3" (an end not above the start is no range)
 *   "N8342"    → prefix "N", number 8342
 *
 * Only the first group of digits is the number: "12/3" is house 12, flat 3. A single separator
 * ("/", "-") and whitespace between the number and the addition are dropped. A value without any
 * digit (house names like "Rose Cottage") cannot be parsed.
 */
final readonly class HouseNumber implements \Stringable
{
    private const PATTERN = '/^(?<prefix>[^0-9]*?)\s*(?<number>[0-9]+)(?:\s*-\s*(?<end>[0-9]+))?(?<rest>.*)$/su';

    /**
     * @param string      $value    the trimmed input
     * @param int         $number   the first number: 12 of "12a", "12/3" and "12-14"
     * @param string|null $addition everything after the number (and range): "a" of "12a", "3" of "12/3"
     * @param int|null    $rangeEnd the end of a range: 14 of "12-14"
     * @param string|null $prefix   text in front of the number: "N" of "N8342"
     */
    private function __construct(
        public string $value,
        public int $number,
        public ?string $addition = null,
        public ?int $rangeEnd = null,
        public ?string $prefix = null,
    ) {
    }

    /**
     * Null when the value contains no digit.
     */
    public static function parse(string|int $value): ?self
    {
        $value = trim((string) $value);

        if (1 !== preg_match(self::PATTERN, $value, $m, \PREG_UNMATCHED_AS_NULL)) {
            return null;
        }

        $number = (int) $m['number'];
        $rangeEnd = null !== $m['end'] ? (int) $m['end'] : null;
        $rest = $m['rest'];

        if (null !== $rangeEnd && $rangeEnd <= $number) {
            $rest = '-'.$m['end'].$rest;
            $rangeEnd = null;
        }

        $addition = trim((string) preg_replace('/^\s*[-\/]?\s*/u', '', $rest));

        return new self(
            $value,
            $number,
            '' === $addition ? null : $addition,
            $rangeEnd,
            '' === $m['prefix'] ? null : $m['prefix'],
        );
    }

    public function isRange(): bool
    {
        return null !== $this->rangeEnd;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
