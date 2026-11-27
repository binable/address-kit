<?php

declare(strict_types=1);

namespace Binable\AddressKit\Language\Fr;

use Binable\AddressKit\Language\AbstractStreetTypes;

/**
 * The "types de voie" a French street name starts with ("Avenue de la Gare",
 * "Bd Voltaire", "Rd-Pt des Vignes"), with the abbreviations of La Poste, the
 * BAN and the communes.
 *
 * Used by {@see \Binable\AddressKit\Permutation\Strategy\Fr\StreetTypePermutation}.
 */
final class StreetTypes extends AbstractStreetTypes
{
    /**
     * Types that are just as often the first word of the name itself ("Le
     * Clos", "La Croix", "Les Jardins") are not listed here but in
     * {@see self::NAME_LIKE_TYPES}.
     */
    public const TYPES = [
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
        // Belgian: the tree-lined avenue of Brussels and Wallonia.
        'drève' => ['drève', 'dreve'],
        'voie' => ['voie'],
        'rue' => ['rue', 'r.'],
    ];

    public const NAME_LIKE_TYPES = [
        'clos' => ['clos'],
        'cité' => ['cité', 'cite'],
        'cour' => ['cour'],
        'parc' => ['parc'],
        'jardins' => ['jardins'],
        'croix' => ['croix'],
        'quartier' => ['quartier', 'qua.'],
        // Walloon: the steep street up a hill ("Thier de la Fontaine").
        'thier' => ['thier'],
    ];
}
