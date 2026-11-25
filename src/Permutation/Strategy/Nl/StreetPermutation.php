<?php

declare(strict_types=1);

namespace Binable\AddressKit\Permutation\Strategy\Nl;

use Binable\AddressKit\Model\Address;
use Binable\AddressKit\Permutation\LocalizedPermutationStrategyInterface;
use Binable\AddressKit\Permutation\Strategy\AbstractPermutation;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

/**
 * Dutch counterpart of {@see \Binable\AddressKit\Permutation\Strategy\De\StrassePermutation}:
 * the abbreviated street names (NEN 5825 "verkorte straatnaam") that Dutch
 * registers use next to the full spelling - "Kerkstraat" / "Kerkstr.",
 * "Julianalaan" / "Julianaln.", "Burgemeester van der Vlugtstraat" /
 * "Burg. v.d. Vlugtstr.".
 *
 * Unlike German, Dutch never separates the generic term from the name part, so
 * only glued spellings are generated.
 */
#[AsTaggedItem(priority: 20)]
final class StreetPermutation extends AbstractPermutation implements LocalizedPermutationStrategyInterface
{
    /**
     * Every spelling of a generic term, first entry is the full one.
     *
     * @var array<string, list<string>>
     */
    private const TERMS = [
        'dwarsstraat' => ['dwarsstraat', 'dwstr.', 'dwstr'],
        'boulevard' => ['boulevard', 'bd.', 'bd'],
        'gracht' => ['gracht', 'gr.', 'gr'],
        'straat' => ['straat', 'str.', 'str'],
        'plein' => ['plein', 'pln.', 'pln', 'pl.'],
        'kade' => ['kade', 'kd.', 'kd'],
        'laan' => ['laan', 'ln.', 'ln'],
        'weg' => ['weg', 'wg.', 'wg'],
    ];

    /**
     * Name particles and titles: full spelling => abbreviation. Both directions
     * are generated, several abbreviations for the same particle are listed
     * separately. The optional third entry skips the pair when it matches, so
     * "van der" is never abbreviated to "v. der".
     *
     * @var list<array{0: string, 1: string, 2?: string}>
     */
    private const ABBREVIATIONS = [
        ['van der ', 'v.d. '],
        ['van der ', 'vd '],
        ['van den ', 'v.d. '],
        ['van de ', 'v.d. '],
        ['van ', 'v. ', '/\bvan de[nr]? /iu'],
        ['burgemeester ', 'burg. '],
        ['doctor ', 'dr. '],
        ['dokter ', 'dr. '],
        ['professor ', 'prof. '],
        ['generaal ', 'gen. '],
        ['koningin ', 'kon. '],
        ['meester ', 'mr. '],
        ['pastoor ', 'past. '],
        ['sint ', 'st. '],
    ];

    public function getLanguage(): string
    {
        return 'nl';
    }

    public function permute(Address $address): iterable
    {
        if (null === $address->street) {
            return;
        }

        $variations = $this->getTermVariations($address->street);

        foreach ([$address->street, ...$variations] as $candidate) {
            foreach ($this->getAbbreviationVariations($candidate) as $variation) {
                $variations[] = $variation;
            }
        }

        foreach (array_unique($variations) as $street) {
            if ($street !== $address->street) {
                yield $address->withStreet($street);
            }
        }
    }

    /**
     * @return list<string>
     */
    private function getTermVariations(string $street): array
    {
        $pattern = self::alternation(array_merge(...array_values(self::TERMS)));

        // The term has to be glued to the name part: "Nieuwe Gracht" is a name
        // of its own and must not become "Nieuwe gr.".
        if (1 !== preg_match('/^(.+?\S)('.$pattern.')$/iu', $street, $matches)) {
            return [];
        }

        $base = $matches[1];
        $found = mb_strtolower($matches[2]);

        $variations = [];
        foreach (self::TERMS as $spellings) {
            if (!\in_array($found, $spellings, true)) {
                continue;
            }

            foreach ($spellings as $spelling) {
                $variations[] = $base.$spelling;
            }
        }

        return $variations;
    }

    /**
     * Every particle and title on its own, plus the completely abbreviated and
     * the completely written out spelling ("Burg. v.d. Vlugtstraat").
     *
     * @return list<string>
     */
    private function getAbbreviationVariations(string $street): array
    {
        $variations = [];
        $abbreviated = $street;
        $expanded = $street;

        foreach (self::ABBREVIATIONS as $abbreviation) {
            [$full, $short] = $abbreviation;
            $guard = $abbreviation[2] ?? null;

            if (null === $guard || 1 !== preg_match($guard, $street)) {
                foreach ([[$full, $short], [$short, $full]] as [$search, $replacement]) {
                    $variations[] = $this->replaceIgnoringCase($street, $search, $replacement);
                }
            }

            if (null === $guard || 1 !== preg_match($guard, $abbreviated)) {
                $abbreviated = $this->replaceIgnoringCase($abbreviated, $full, $short);
            }

            $expanded = $this->replaceIgnoringCase($expanded, $short, $full);
        }

        $variations[] = $abbreviated;
        $variations[] = $expanded;

        return array_values(array_filter($variations, static fn (string $variation): bool => $variation !== $street));
    }

    private function replaceIgnoringCase(string $subject, string $search, string $replacement): string
    {
        return preg_replace_callback(
            '/\b'.preg_quote($search, '/').'/iu',
            static fn (array $match): string => mb_strtoupper(mb_substr($match[0], 0, 1)) === mb_substr($match[0], 0, 1)
                ? self::ucfirst($replacement)
                : $replacement,
            $subject,
        ) ?? $subject;
    }
}
