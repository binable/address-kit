<?php

declare(strict_types=1);

namespace Binable\AddressKit\Permutation\Strategy;

use Binable\AddressKit\Permutation\PermutationStrategyInterface;

use function Symfony\Component\String\u;

/**
 * Shared helpers for the built-in strategies: word replacement that keeps the capitalisation and
 * the abbreviation pairs (full spelling <-> abbreviation) most languages need.
 */
abstract class AbstractPermutation implements PermutationStrategyInterface
{
    /**
     * True for the words a dedicated name is built from: "Gaulle", "Marconi",
     * "Kościuszki", "8", "1945" - false for the articles and particles in
     * between ("du", "de", "la", "dei", "im").
     */
    protected function isNameWord(string $word): bool
    {
        return 1 === preg_match('/^[\p{Lu}\d]/u', $word);
    }

    /**
     * Replaces a whole word, keeping its capitalisation.
     *
     * `\b` is not used on purpose: without (*UCP) it does not match in front
     * of "é", so "/\bécole\b/" would never fire. A following dot ends the
     * match as well, otherwise "St." would be expanded to "Saint." by the
     * "st" rule before the "st." rule ever sees it.
     *
     * An abbreviation carrying a dot is never followed by a hyphen either:
     * "St-Étienne" and "St. Étienne" exist, "St.-Étienne" does not.
     */
    protected function replaceWord(string $subject, string $search, string $replacement): string
    {
        $tail = str_ends_with($replacement, '.') ? '\p{L}\d.-' : '\p{L}\d.';

        return preg_replace_callback(
            '/(?<![\p{L}\d])'.preg_quote($search, '/').'(?!['.$tail.'])/iu',
            fn (array $match): string => mb_strtoupper(mb_substr($match[0], 0, 1)) === mb_substr($match[0], 0, 1)
                ? $this->capitalise($replacement)
                : $replacement,
            $subject,
        ) ?? $subject;
    }

    /**
     * Every spelling of the abbreviated words in a name: each pair on its own,
     * plus the completely abbreviated and the completely written out one
     * ("Gl. Skt. Jørgens Vej").
     *
     * The pairs are applied twice, to the name and to its written out form.
     * A word with two abbreviations is only reachable that way: "Skt." has to
     * become "Sankt" before it can become "St.".
     *
     * @param list<array{string, string}> $pairs full spelling => abbreviation
     *
     * @return iterable<string>
     */
    protected function getPairVariations(string $value, array $pairs): iterable
    {
        $variations = [];

        foreach ([$value, $this->applyPairs($value, $pairs, true)] as $candidate) {
            foreach ($pairs as [$full, $short]) {
                $variations[] = $this->replaceWord($candidate, $full, $short);
                $variations[] = $this->replaceWord($candidate, $short, $full);
            }

            $variations[] = $this->applyPairs($candidate, $pairs, false);
            $variations[] = $this->applyPairs($candidate, $pairs, true);
        }

        foreach (array_unique($variations) as $variation) {
            if ($variation !== $value) {
                yield $variation;
            }
        }
    }

    /**
     * Capitalisation of a replacement that stands in for a capitalised word.
     * Every word is capitalised, so "nd" becomes "Notre-Dame" and not
     * "Notre-dame".
     *
     * Languages whose abbreviations carry an inner dot ("V.le", "P.zza",
     * "F.lli") override this - there the second half is not a word of its own.
     */
    protected function capitalise(string $value): string
    {
        return u($value)->title(true)->toString();
    }

    /**
     * Upper case first letter only ("piazza" -> "Piazza", "p.zza" -> "P.zza").
     */
    protected static function ucfirst(string $value): string
    {
        return u($value)->title()->toString();
    }

    /**
     * Regex alternation of the given values, longest first so "An der" wins over "An".
     *
     * @param list<string> $values
     */
    protected static function alternation(array $values): string
    {
        usort($values, static fn (string $left, string $right): int => mb_strlen($right) <=> mb_strlen($left));

        return implode('|', array_map(static fn (string $value): string => preg_quote($value, '/'), $values));
    }

    /**
     * Applies every pair to the name at once, so a name carrying several
     * titles ends up completely abbreviated or completely written out.
     *
     * @param list<array{string, string}> $pairs
     */
    private function applyPairs(string $value, array $pairs, bool $expand): string
    {
        foreach ($pairs as [$full, $short]) {
            $value = $expand
                ? $this->replaceWord($value, $short, $full)
                : $this->replaceWord($value, $full, $short);
        }

        return $value;
    }
}
