<?php

declare(strict_types=1);

namespace Binable\AddressKit\Language\It;

use Binable\AddressKit\Language\AbstractStreetTypes;

/**
 * The "tipi di via" an Italian street name starts with ("Via Roma",
 * "P.zza Garibaldi", "C.so Italia"), with the abbreviations of the comuni,
 * ISTAT and Poste Italiane.
 *
 * Used by {@see \Binable\AddressKit\Permutation\Strategy\It\StreetTypePermutation}.
 */
final class StreetTypes extends AbstractStreetTypes
{
    /**
     * Terms that are just as often the first word of the name itself ("Ponte
     * Vecchio", "Villa Serena", "Parco della Vittoria") are listed in
     * {@see self::NAME_LIKE_TYPES}. The bare "P." is not listed at all - it
     * stands for piazza, piazzale, passaggio and for the initial of a given
     * name alike.
     */
    public const TYPES = [
        'circonvallazione' => ['circonvallazione', 'circonv.', 'circ.ne'],
        'lungomare' => ['lungomare', 'l.mare', 'lgm.'],
        'lungargine' => ['lungargine'],
        'quartiere' => ['quartiere', 'quart.', 'q.re'],
        'frazione' => ['frazione', 'fraz.', 'fz.'],
        'località' => ['località', 'loc.', 'localita', "localita'"],
        'piazzetta' => ['piazzetta', 'p.tta', 'pz.tta'],
        'piazzale' => ['piazzale', 'p.le', 'ple', 'pzle'],
        'traversa' => ['traversa', 'trav.', 'tr.'],
        'contrada' => ['contrada', 'c.da', 'ctr.', 'cda'],
        // Venetian: the street of Vicenza and its province ("Contrà Porti").
        'contrà' => ['contrà', 'contra', "contra'"],
        'galleria' => ['galleria', 'gall.', 'gall'],
        'borgata' => ['borgata', 'b.ta', 'bg.ta'],
        'discesa' => ['discesa', 'disc.'],
        'stradone' => ['stradone', 'strad.ne'],
        'stradella' => ['stradella'],
        'stradello' => ['stradello'],
        'passeggiata' => ['passeggiata'],
        'vocabolo' => ['vocabolo', 'voc.'],
        'vicolo' => ['vicolo', 'vic.', 'v.lo', 'vco'],
        'piazza' => ['piazza', 'p.zza', 'p.za', 'pzza', 'pza'],
        'strada' => ['strada', 'str.', 's.da'],
        'salita' => ['salita', 'sal.'],
        'rione' => ['rione', 'r.ne'],
        'viale' => ['viale', 'v.le', 'vle'],
        'corso' => ['corso', 'c.so', 'cso'],
        'largo' => ['largo', 'l.go', 'lgo'],
        'borgo' => ['borgo', 'b.go', 'bgo'],
        'calle' => ['calle'],
        'campo' => ['campo', 'c.po'],
        'via' => ['via', 'v.'],
    ];

    public const NAME_LIKE_TYPES = [
        'ponte' => ['ponte'],
        'villa' => ['villa'],
        'parco' => ['parco'],
        'riviera' => ['riviera'],
        'rotonda' => ['rotonda'],
        'masseria' => ['masseria'],
        'zona' => ['zona'],
    ];
}
