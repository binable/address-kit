<?php

declare(strict_types=1);

namespace Binable\AddressKit\Language\Lt;

use Binable\AddressKit\Language\AbstractStreetTypes;

/**
 * The generic terms that end a Lithuanian street name ("Vilniaus g.",
 * "Gedimino pr.", "Laisvės al."), with the abbreviations of the address
 * register of the Registrų centras, which writes the term abbreviated and in a
 * column of its own. The folded spellings ("gatve", "aikste") come from
 * municipal exports.
 *
 * Used by {@see \Binable\AddressKit\Permutation\Strategy\Lt\StreetTypePermutation}.
 */
final class StreetTypes extends AbstractStreetTypes
{
    public const TYPES = [
        'skersgatvis' => ['skersgatvis', 'skg.'],
        'akligatvis' => ['akligatvis', 'aklg.'],
        'prospektas' => ['prospektas', 'pr.', 'prosp.'],
        'kvartalas' => ['kvartalas', 'kv.'],
        'krantinė' => ['krantinė', 'krantine', 'krant.'],
        'plentas' => ['plentas', 'pl.'],
        'skveras' => ['skveras', 'skv.'],
        'kelias' => ['kelias', 'kel.'],
        'aikštė' => ['aikštė', 'aikste', 'a.'],
        'žiedas' => ['žiedas', 'ziedas'],
        'gatvė' => ['gatvė', 'gatve', 'g.'],
        'alėja' => ['alėja', 'aleja', 'al.'],
        'takas' => ['takas', 'tak.'],
        'sodai' => ['sodai', 'sod.'],
    ];

    public const NAME_LIKE_TYPES = [
        'parkas' => ['parkas'],
        'tiltas' => ['tiltas'],
    ];
}
