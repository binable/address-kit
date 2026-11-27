<?php

declare(strict_types=1);

namespace Binable\AddressKit\Language\Ro;

use Binable\AddressKit\Language\AbstractStreetTypes;

/**
 * The generic terms a Romanian street name starts with ("Str. Mihai
 * Eminescu", "B-dul Unirii", "Calea Victoriei"), in the articulated form the
 * registers use, with their abbreviations.
 *
 * Romanian has two spellings of "ș" and "ț": the correct comma below and the
 * cedilla most older systems still store ("Şoseaua", "Piaţa"). Both are
 * listed, as is the folded spelling.
 *
 * Used by {@see \Binable\AddressKit\Permutation\Strategy\Ro\StreetTypePermutation}.
 */
final class StreetTypes extends AbstractStreetTypes
{
    public const TYPES = [
        'bulevardul' => ['bulevardul', 'bd.', 'bd', 'b-dul', 'b-dul.', 'bdul.', 'bdul', 'bulevard', 'blvd.'],
        'prelungirea' => ['prelungirea', 'prel.'],
        'fundătura' => ['fundătura', 'fundatura', 'fdt.', 'fnd.'],
        'cartierul' => ['cartierul', 'cartier', 'cart.'],
        'intrarea' => ['intrarea', 'intr.', 'int.'],
        'șoseaua' => ['șoseaua', 'şoseaua', 'soseaua', 'șos.', 'şos.', 'sos.'],
        'stradela' => ['stradela', 'str-la'],
        'splaiul' => ['splaiul', 'spl.', 'splai'],
        'piațeta' => ['piațeta', 'piaţeta', 'piateta'],
        'pasajul' => ['pasajul', 'pasaj', 'pas.'],
        'drumul' => ['drumul', 'drum'],
        'strada' => ['strada', 'str.', 'str'],
        'piața' => ['piața', 'piaţa', 'piata', 'pța.', 'pţa.', 'pta.', 'p-ța', 'p-ţa', 'p-ta'],
        'ulița' => ['ulița', 'uliţa', 'ulita'],
        'aleea' => ['aleea', 'al.'],
        'calea' => ['calea', 'cal.'],
    ];

    public const NAME_LIKE_TYPES = [
        'parcul' => ['parcul', 'parc'],
        'cheiul' => ['cheiul'],
    ];
}
