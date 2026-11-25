<?php

declare(strict_types=1);

namespace Binable\AddressKit\Permutation\Strategy\Fr;

use Binable\AddressKit\Permutation\LocalizedPermutationStrategyInterface;
use Binable\AddressKit\Permutation\Strategy\AbstractLeadingTypePermutation;

/**
 * Base class for permutations that only make sense for French street names
 * ("Avenue du Général de Gaulle", "Rue Saint-Étienne", "Bd Voltaire").
 *
 * French street names are built the other way round than German ones: the
 * generic term ("type de voie") comes first and is never glued to the name
 * part, while the name part itself is hyphenated. Both halves are therefore
 * handled by their own permutation - {@see StreetTypePermutation} for the type
 * de voie and {@see HyphenPermutation} for the name.
 *
 * In multilingual countries (BE, CH) the Dutch and German street names stay
 * untouched: every permutation of this namespace needs a French type de voie
 * or a French-only diacritic to fire.
 */
abstract class AbstractFrenchPermutation extends AbstractLeadingTypePermutation implements LocalizedPermutationStrategyInterface
{
    /**
     * Every spelling of a "type de voie", the generic term a French street
     * name starts with. First entry is the full one, the others are the
     * abbreviations the registers of La Poste, the BAN and the communes use.
     *
     * Types that are just as often the first word of the name itself are left
     * out on purpose ("Le Clos", "La Croix", "Les Jardins"): permuting those
     * only produces dead lookups.
     *
     * @var array<string, list<string>>
     */
    protected const TYPES = [
        'rond-point' => ['rond-point', 'rd-pt', 'rd pt', 'rpt'],
        'lotissement' => ['lotissement', 'lot.', 'lot'],
        'résidence' => ['résidence', 'rés.', 'res.', 'residence'],
        'esplanade' => ['esplanade', 'espl.', 'esp.'],
        'promenade' => ['promenade', 'prom.'],
        'grande rue' => ['grande rue', 'gde rue', 'grand rue'],
        'boulevard' => ['boulevard', 'bd', 'bd.', 'bld', 'blvd', 'boul.'],
        'chaussée' => ['chaussée', 'chée', 'chaussee'],
        'faubourg' => ['faubourg', 'fbg', 'fg'],
        'traverse' => ['traverse', 'trav.'],
        'passage' => ['passage', 'pass.', 'psg'],
        'sentier' => ['sentier', 'sent.', 'sen.'],
        'impasse' => ['impasse', 'imp.', 'imp'],
        // "avenu" is the common typo with the final e dropped ("Avenu des Mimosas").
        'avenue' => ['avenue', 'av.', 'av', 'ave', 'avenu'],
        'hameau' => ['hameau', 'ham.', 'hme'],
        'domaine' => ['domaine', 'dom.'],
        'montée' => ['montée', 'mtée', 'montee'],
        'venelle' => ['venelle', 'ven.'],
        'ruelle' => ['ruelle', 'rle'],
        'square' => ['square', 'sq.', 'sq'],
        'chemin' => ['chemin', 'chem.', 'ch.', 'che'],
        'allée' => ['allée', 'all.', 'all', 'allee'],
        'place' => ['place', 'pl.', 'pl'],
        'route' => ['route', 'rte', 'rte.'],
        'cours' => ['cours', 'crs'],
        'villa' => ['villa', 'vla'],
        'quai' => ['quai', 'qu.'],
        'voie' => ['voie'],
        'rue' => ['rue', 'r.'],
    ];

    public function getLanguage(): string
    {
        return 'fr';
    }
}
