<?php

declare(strict_types=1);

namespace Binable\AddressKit\Normalizer\Encoding;

use Binable\AddressKit\Model\AddressField;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

/**
 * Repairs mojibake: UTF-8 bytes that were wrongly decoded as Windows-1252 / ISO-8859-1,
 * e.g. "Ã¼" → "ü", "Ã©" → "é", "â€™" → "’" (typical for wrongly imported CSV files).
 *
 * Every run of non-ASCII characters is re-encoded to Windows-1252 bytes; the run is only replaced
 * if these bytes form valid UTF-8. Correct text ("Müller") never does, so it stays untouched, and
 * strings mixing correct and broken parts are repaired as well. Double encoded text is repaired
 * in up to {@see MAX_ROUNDS} rounds.
 */
#[AsTaggedItem(priority: 800)]
final class MojibakeNormalizer implements StringNormalizerInterface
{
    private const MAX_ROUNDS = 3;

    /**
     * Characters of the Windows-1252 range 0x80–0x9F that differ from ISO-8859-1.
     */
    private const WINDOWS_1252 = [
        0x20AC => 0x80, 0x201A => 0x82, 0x0192 => 0x83, 0x201E => 0x84, 0x2026 => 0x85, 0x2020 => 0x86,
        0x2021 => 0x87, 0x02C6 => 0x88, 0x2030 => 0x89, 0x0160 => 0x8A, 0x2039 => 0x8B, 0x0152 => 0x8C,
        0x017D => 0x8E, 0x2018 => 0x91, 0x2019 => 0x92, 0x201C => 0x93, 0x201D => 0x94, 0x2022 => 0x95,
        0x2013 => 0x96, 0x2014 => 0x97, 0x02DC => 0x98, 0x2122 => 0x99, 0x0161 => 0x9A, 0x203A => 0x9B,
        0x0153 => 0x9C, 0x017E => 0x9E, 0x0178 => 0x9F,
    ];

    public function normalize(string $value, ?AddressField $field = null): string
    {
        for ($round = 0; $round < self::MAX_ROUNDS; ++$round) {
            $repaired = (string) preg_replace_callback(
                '/[^\x00-\x7F]+/u',
                fn (array $m): string => $this->repairRun($m[0]),
                $value,
            );

            if ($repaired === $value) {
                break;
            }
            $value = $repaired;
        }

        return $value;
    }

    private function repairRun(string $run): string
    {
        $bytes = '';
        foreach (mb_str_split($run) as $char) {
            $codePoint = mb_ord($char);
            $byte = $codePoint < 0x100 ? max(0, $codePoint) : (self::WINDOWS_1252[$codePoint] ?? null);

            if (null === $byte) {
                return $run; // not representable as a single byte, so this can't be mojibake
            }
            $bytes .= \chr($byte);
        }

        return mb_check_encoding($bytes, 'UTF-8') ? $bytes : $run;
    }
}
