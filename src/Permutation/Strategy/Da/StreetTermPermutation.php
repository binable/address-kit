<?php

declare(strict_types=1);

namespace Binable\AddressKit\Permutation\Strategy\Da;

use Binable\AddressKit\Model\Address;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

/**
 * The trailing generic term in its indefinite and its definite form:
 * "Skovvej" / "Skovvejen", "Hovedgade" / "Hovedgaden", "Kirketorv" /
 * "Kirketorvet", "Bygaden" / "Bygade".
 *
 * Danish counterpart of
 * {@see \Binable\AddressKit\Permutation\Strategy\De\GrundwortPermutation}, only
 * the other way round: German splits the term off the name, Danish hardly ever
 * does. What varies is the definite article glued behind it, and the two
 * spellings of "allé" that every register mixes up.
 *
 * The one place the term does stand apart is behind a name of several words,
 * mostly a saint's: "Sankt Hans Gade" is commonly written "Sct. Hansgade". For
 * such names both the glued and the separated spelling are generated, in
 * either direction.
 */
#[AsTaggedItem(priority: 10)]
final class StreetTermPermutation extends AbstractDanishPermutation
{
    /**
     * Every spelling of a generic term, indefinite first. All lowercase, the
     * term is always glued to the name.
     *
     * Terms that are just as often the tail of a place name are left out on
     * purpose: "…holm" (Bornholm), "…borg" (Aalborg), "…lev" (Ballerup),
     * "…bro" (Nørrebro). Splitting or inflecting those only produces dead
     * lookups.
     *
     * @var array<string, list<string>>
     */
    private const TERMS = [
        'stræde' => ['stræde', 'strædet'],
        'vænge' => ['vænge', 'vænget'],
        'bakke' => ['bakke', 'bakken'],
        'plads' => ['plads', 'pladsen'],
        'gade' => ['gade', 'gaden'],
        'park' => ['park', 'parken'],
        'torv' => ['torv', 'torvet'],
        'lund' => ['lund', 'lunden'],
        'mark' => ['mark', 'marken'],
        'krog' => ['krog', 'krogen'],
        'toft' => ['toft', 'toften'],
        'have' => ['have', 'haven'],
        'gård' => ['gård', 'gården', 'gaard', 'gaarden'],
        'allé' => ['allé', 'alléen', 'alle', 'alleen', 'allee', 'alleén'],
        'vej' => ['vej', 'vejen'],
        'sti' => ['sti', 'stien'],
        'eng' => ['eng', 'engen'],
        'dal' => ['dal', 'dalen'],
    ];

    public function permute(Address $address): iterable
    {
        $street = $address->street;
        if (null === $street) {
            return;
        }

        $pattern = self::alternation(array_merge(...array_values(self::TERMS)));

        // The term has to be glued to a name of its own: "Ved Bakken" is a
        // phrase and must not become "Ved Bakke". A single letter in front of
        // it is enough though - "Ågade" and "Ågaden" both exist.
        $separated = false;
        if (1 !== preg_match('/^(\S(?:.*?\S)?)('.$pattern.')$/iu', $street, $matches)) {
            // Behind a name of several words the term may stand on its own -
            // "Sankt Hans Gade", "Sankt Peders Stræde" - and the street sign
            // regularly glues it ("Sct. Hansgade") where the register does not.
            if (1 !== preg_match('/^(\S+ \S(?:.*?\S)?) ('.$pattern.')$/iu', $street, $matches)) {
                return;
            }

            $separated = true;
        }

        $base = $matches[1];
        $found = mb_strtolower($matches[2]);
        // Only a name of several words may carry a separated term: splitting
        // "Nørregade" into "Nørre Gade" produces nothing a register stores.
        $multiWord = str_contains($base, ' ');

        foreach (self::TERMS as $spellings) {
            if (!\in_array($found, $spellings, true)) {
                continue;
            }

            foreach ($spellings as $spelling) {
                $glued = $base.$spelling;
                if ($separated || $glued !== $street) {
                    yield $address->withStreet($glued);
                }

                if (!$multiWord) {
                    continue;
                }

                $apart = $base.' '.mb_strtoupper(mb_substr($spelling, 0, 1)).mb_substr($spelling, 1);
                if ($apart !== $street) {
                    yield $address->withStreet($apart);
                }
            }
        }
    }
}
