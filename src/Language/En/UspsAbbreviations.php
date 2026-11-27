<?php

declare(strict_types=1);

namespace Binable\AddressKit\Language\En;

/**
 * Street suffixes and directionals as USPS Publication 28 abbreviates them
 * ("Boulevard" -> "Blvd", "Northeast" -> "NE"), full spelling => abbreviation.
 *
 * American registers write exactly one abbreviation per suffix, so unlike the
 * other dictionaries this one is a plain map. The street types of Great
 * Britain, Ireland, Australia, New Zealand and South Africa, which abbreviate
 * differently ("Cres", "Tce", "Pde") and know several spellings per type, are
 * in {@see StreetTypes}.
 *
 * Used by the permutations of {@see \Binable\AddressKit\Permutation\Strategy\En}.
 */
final class UspsAbbreviations
{
    /**
     * @var array<string, string>
     */
    public const SUFFIXES = [
        'Alley' => 'Aly', 'Avenue' => 'Ave', 'Boulevard' => 'Blvd', 'Circle' => 'Cir',
        'Court' => 'Ct', 'Crescent' => 'Cres', 'Crossing' => 'Xing', 'Drive' => 'Dr',
        'Expressway' => 'Expy', 'Freeway' => 'Fwy', 'Grove' => 'Grv', 'Heights' => 'Hts',
        'Highway' => 'Hwy', 'Lane' => 'Ln', 'Loop' => 'Loop', 'Parkway' => 'Pkwy',
        'Place' => 'Pl', 'Plaza' => 'Plz', 'Ridge' => 'Rdg', 'Road' => 'Rd',
        'Square' => 'Sq', 'Street' => 'St', 'Terrace' => 'Ter', 'Trail' => 'Trl',
        'Way' => 'Way',
    ];

    /**
     * @var array<string, string>
     */
    public const DIRECTIONALS = [
        'Northeast' => 'NE', 'Northwest' => 'NW', 'Southeast' => 'SE', 'Southwest' => 'SW',
        'North' => 'N', 'South' => 'S', 'East' => 'E', 'West' => 'W',
    ];

    private function __construct()
    {
    }
}
