<?php

declare(strict_types=1);

namespace Binable\AddressKit\Language\Cs;

use Binable\AddressKit\Language\AbstractStreetTypes;

/**
 * The generic terms of Czech street names. They stand in front of a name in
 * the genitive ("náměstí Míru", "třída Svobody") and behind an adjective
 * ("Václavské náměstí", "Národní třída"); both are written in the RÚIAN
 * register, the abbreviations ("nám.", "tř.") and the folded spellings
 * ("namesti") come from user input and municipal exports.
 *
 * "ulice" is not part of a single name in RÚIAN, but turns up in front of
 * every other input ("ul. Masarykova").
 *
 * Used by {@see \Binable\AddressKit\Permutation\Strategy\Cs\StreetTypePermutation}.
 */
final class StreetTypes extends AbstractStreetTypes
{
    public const TYPES = [
        'náměstí' => ['náměstí', 'namesti', 'nám.', 'nam.', 'nám', 'nam'],
        'nábřeží' => ['nábřeží', 'nabrezi', 'nábř.', 'nabr.'],
        'sídliště' => ['sídliště', 'sidliste', 'sídl.', 'sidl.'],
        'třída' => ['třída', 'trida', 'tř.', 'tr.', 'tř', 'tr'],
        'ulice' => ['ulice', 'ul.', 'ul'],
    ];

    /**
     * Just as often a name of their own: "Riegrovy sady", "Karlův most",
     * "Lipová alej".
     */
    public const NAME_LIKE_TYPES = [
        'sady' => ['sady'],
        'alej' => ['alej'],
        'most' => ['most'],
        'cesta' => ['cesta'],
        'stezka' => ['stezka'],
        'park' => ['park'],
    ];
}
