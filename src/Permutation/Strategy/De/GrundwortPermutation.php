<?php

declare(strict_types=1);

namespace Binable\AddressKit\Permutation\Strategy\De;

use Binable\AddressKit\Model\Address;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

/**
 * Generic terms other than "Straße" that are written glued to the name part in
 * one register and separated in the next: "Schürhornweg" / "Schürhorn Weg",
 * "Marktplatz" / "Markt Platz" / "Marktpl.", "Lindenallee" / "Linden-Allee".
 *
 * The generic term "Straße" has its own class, {@see StrassePermutation},
 * because it is the only one with an established abbreviation and because it
 * carries by far the most spellings.
 */
#[AsTaggedItem(priority: 20)]
final class GrundwortPermutation extends AbstractGermanPermutation
{
    /**
     * Glued term => additional lowercase spellings of the same term.
     *
     * Terms that are just as often the tail of a place or family name are left
     * out on purpose: "…berg" (Heidelberg), "…hof" (Bahnhof), "…feld"
     * (Bielefeld), "…tal", "…bach", "…dorf". Splitting those only produces dead
     * lookups.
     *
     * @var array<string, list<string>>
     */
    public const TERMS = [
        'weg' => [],
        'platz' => ['pl.'],
        'allee' => [],
        'gasse' => [],
        'ring' => [],
        'damm' => [],
        'ufer' => [],
        'steig' => [],
        'stieg' => [],
        'graben' => [],
        'markt' => [],
        'garten' => [],
        'park' => [],
        'anger' => [],
        'winkel' => [],
        'zeile' => [],
        'bogen' => [],
        'kamp' => [],
        'kehre' => [],
        'twiete' => [],
        'wall' => [],
        'pfad' => [],
        'höhe' => [],
        'aue' => [],
        'chaussee' => ['ch.'],
    ];

    public function permute(Address $address): iterable
    {
        if (null === $address->street || 1 !== preg_match('/^(.+?)[ -]?('.implode('|', array_keys(self::TERMS)).')$/iu', $address->street, $matches)) {
            return;
        }

        $term = mb_strtolower($matches[2]);
        $glued = [$term, ...self::TERMS[$term] ?? []];
        $separated = array_map(self::ucfirst(...), $glued);

        yield from $this->getTermCombinations($address, $matches[1], $glued, $separated);
    }
}
