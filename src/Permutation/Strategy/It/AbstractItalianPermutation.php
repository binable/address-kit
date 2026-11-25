<?php

declare(strict_types=1);

namespace Binable\AddressKit\Permutation\Strategy\It;

use Binable\AddressKit\Permutation\LocalizedPermutationStrategyInterface;
use Binable\AddressKit\Permutation\Strategy\AbstractLeadingTypePermutation;

/**
 * Base class for permutations that only make sense for Italian street names
 * ("Via Guglielmo Marconi", "P.zza Sant'Agostino", "C.so Vittorio Emanuele").
 *
 * Italian street names are built like the French ones: the generic term
 * ("tipo di via") comes first, separated by a space and never glued to the
 * name. What is Italian and has no French counterpart is the amount of
 * abbreviations with an inner dot ("V.le", "P.zza", "C.so", "F.lli") and the
 * elision in front of a vowel ("Sant'Agostino").
 *
 * The German street names of South Tyrol and the German and French ones of
 * Switzerland stay untouched: every permutation of this namespace needs an
 * Italian tipo di via to fire.
 */
abstract class AbstractItalianPermutation extends AbstractLeadingTypePermutation implements LocalizedPermutationStrategyInterface
{
    /**
     * Every spelling of a "tipo di via". First entry is the full one, the
     * others are the abbreviations the comuni, ISTAT and Poste Italiane use.
     *
     * Terms that are just as often the first word of the name itself are left
     * out on purpose ("Ponte Vecchio", "Villa Serena", "Parco della Vittoria"):
     * permuting those only produces dead lookups. The bare "P." is left out for
     * the same reason - it stands for piazza, piazzale, passaggio and for the
     * initial of a given name alike.
     *
     * @var array<string, list<string>>
     */
    protected const TYPES = [
        'circonvallazione' => ['circonvallazione', 'circonv.', 'circ.ne'],
        'lungomare' => ['lungomare', 'l.mare', 'lgm.'],
        'quartiere' => ['quartiere', 'quart.', 'q.re'],
        'frazione' => ['frazione', 'fraz.', 'fz.'],
        'località' => ['località', 'loc.', 'localita', "localita'"],
        'piazzetta' => ['piazzetta', 'p.tta', 'pz.tta'],
        'piazzale' => ['piazzale', 'p.le', 'ple', 'pzle'],
        'traversa' => ['traversa', 'trav.', 'tr.'],
        'contrada' => ['contrada', 'c.da', 'ctr.'],
        'galleria' => ['galleria', 'gall.', 'gall'],
        'borgata' => ['borgata', 'b.ta', 'bg.ta'],
        'discesa' => ['discesa', 'disc.'],
        'stradone' => ['stradone', 'strad.ne'],
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

    public function getLanguage(): string
    {
        return 'it';
    }

    /**
     * Only the first letter, never every word: the Italian abbreviations carry
     * an inner dot ("P.zza", "V.le", "C.so", "F.lli") and the part behind it is
     * a fragment of the same word, not a second one.
     */
    protected function capitalise(string $value): string
    {
        return self::ucfirst($value);
    }
}
