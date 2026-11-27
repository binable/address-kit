<?php

declare(strict_types=1);

namespace Binable\AddressKit\Language\Lv;

use Binable\AddressKit\Language\AbstractStreetTypes;

/**
 * The generic terms that end a Latvian street name ("Brīvības iela",
 * "Kronvalda bulvāris"), with their folded spellings ("bulvaris", "cels").
 *
 * Latvian registers hardly abbreviate, so there is no permutation building on
 * this dictionary yet - it is meant for code that strips or compares the term.
 */
final class StreetTypes extends AbstractStreetTypes
{
    public const TYPES = [
        'maģistrāle' => ['maģistrāle', 'magistrale'],
        'šķērsiela' => ['šķērsiela', 'skersiela'],
        'prospekts' => ['prospekts', 'prosp.'],
        'bulvāris' => ['bulvāris', 'bulvaris', 'bulv.'],
        'krastmala' => ['krastmala'],
        'laukums' => ['laukums', 'lauk.'],
        'šoseja' => ['šoseja', 'soseja'],
        'līnija' => ['līnija', 'linija'],
        'dambis' => ['dambis'],
        'aleja' => ['aleja', 'al.'],
        'gatve' => ['gatve'],
        'iela' => ['iela'],
        'ceļš' => ['ceļš', 'cels'],
    ];

    public const NAME_LIKE_TYPES = [
        'parks' => ['parks'],
        'tilts' => ['tilts'],
    ];
}
