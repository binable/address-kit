<?php

declare(strict_types=1);

namespace Binable\AddressKit\Language\Pt;

use Binable\AddressKit\Language\AbstractStreetTypes;

/**
 * The "tipos de via" a Portuguese street name starts with ("Rua da Liberdade",
 * "Av. da República", "Pç. do Comércio"), with the abbreviations of the CTT
 * and the câmaras municipais. The folded spellings ("praca", "calcada") are
 * listed as well - many municipal exports cannot store "ç".
 *
 * Used by {@see \Binable\AddressKit\Permutation\Strategy\Pt\StreetTypePermutation}.
 */
final class StreetTypes extends AbstractStreetTypes
{
    public const TYPES = [
        'urbanização' => ['urbanização', 'urbanizacao', 'urb.', 'urb'],
        'azinhaga' => ['azinhaga', 'az.'],
        'travessa' => ['travessa', 'tv.', 'tv', 'trav.', 'trav'],
        'alameda' => ['alameda', 'al.', 'al'],
        'avenida' => ['avenida', 'av.', 'av', 'avda'],
        'calçada' => ['calçada', 'calcada', 'cc.', 'calç.'],
        'caminho' => ['caminho', 'cam.', 'cmo'],
        'estrada' => ['estrada', 'estr.', 'est.', 'estr'],
        'praceta' => ['praceta', 'pct.', 'pcta'],
        'rotunda' => ['rotunda', 'rot.'],
        'escadas' => ['escadas', 'esc.'],
        'bairro' => ['bairro', 'br.', 'bº', 'b.º'],
        'praça' => ['praça', 'praca', 'pç.', 'pc.', 'pc', 'pça', 'pca'],
        'largo' => ['largo', 'lg.', 'lg', 'lgo'],
        'lugar' => ['lugar', 'lug.'],
        'beco' => ['beco', 'bc.', 'bco'],
        'rua' => ['rua', 'r.', 'r'],
    ];

    /**
     * Just as often the first word of the name itself: "Quinta da Regaleira",
     * "Vila Nova", "Parque das Nações".
     */
    public const NAME_LIKE_TYPES = [
        'quinta' => ['quinta', 'qta.', 'qta'],
        'vila' => ['vila'],
        'parque' => ['parque', 'pq.'],
        'jardim' => ['jardim', 'jd.'],
        'sítio' => ['sítio', 'sitio'],
        'cruzamento' => ['cruzamento'],
    ];
}
