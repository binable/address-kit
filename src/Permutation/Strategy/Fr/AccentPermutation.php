<?php

declare(strict_types=1);

namespace Binable\AddressKit\Permutation\Strategy\Fr;

use Binable\AddressKit\Model\Address;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

/**
 * Strips the French diacritics and puts them back ("Rue du Général Leclerc"
 * <-> "Rue du General Leclerc", "Genève" <-> "Geneve"), plus the typographic
 * apostrophe many registers store instead of the plain one ("Rue de l’Église"
 * <-> "Rue de l'Église").
 *
 * Stripping is applied as a whole and never rule by rule, exactly like
 * {@see \Binable\AddressKit\Permutation\Strategy\De\UmlautPermutation}: a
 * register either stores the accented spelling or the folded one, it never
 * mixes both within one name.
 *
 * The opposite direction is ambiguous - "e" can stand for "é", "è" or "ê" - so
 * it is not derived but looked up in a list of the words French street names
 * are actually built from.
 *
 * "ä", "ö" and "ü" are deliberately not folded here: they are German, not
 * French, and Switzerland is served by both languages. Folding them would
 * turn every German-Swiss street into a second, dead lookup.
 */
#[AsTaggedItem(priority: 100)]
final class AccentPermutation extends AbstractFrenchPermutation
{
    /**
     * Accented character => its folded spelling. This direction is never
     * ambiguous.
     *
     * @var array<string, string>
     */
    private const TO_ASCII = [
        'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'å' => 'a',
        'ç' => 'c',
        'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
        'í' => 'i', 'î' => 'i', 'ï' => 'i',
        'ñ' => 'n',
        'ó' => 'o', 'ô' => 'o', 'õ' => 'o',
        'ú' => 'u', 'ù' => 'u', 'û' => 'u',
        'ý' => 'y', 'ÿ' => 'y',
        'æ' => 'ae', 'œ' => 'oe',
        'À' => 'A', 'Á' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Å' => 'A',
        'Ç' => 'C',
        'É' => 'E', 'È' => 'E', 'Ê' => 'E', 'Ë' => 'E',
        'Í' => 'I', 'Î' => 'I', 'Ï' => 'I',
        'Ñ' => 'N',
        'Ó' => 'O', 'Ô' => 'O', 'Õ' => 'O',
        'Ú' => 'U', 'Ù' => 'U', 'Û' => 'U',
        'Ý' => 'Y', 'Ÿ' => 'Y',
        'Æ' => 'Ae', 'Œ' => 'Oe',
    ];

    /**
     * Folded word => accented word. Only words that carry an accent in every
     * French street name they appear in are listed; "gare", "moulin",
     * "pasteur", "voltaire" and the like have none and would only bloat the
     * lookup.
     *
     * @var array<string, string>
     */
    private const TO_ACCENT = [
        // generic vocabulary
        'eglise' => 'église', 'eglises' => 'églises',
        'ecole' => 'école', 'ecoles' => 'écoles',
        'chateau' => 'château', 'chateaux' => 'châteaux',
        'hotel' => 'hôtel', 'hopital' => 'hôpital',
        'republique' => 'république', 'prefecture' => 'préfecture',
        'liberte' => 'liberté', 'egalite' => 'égalité', 'fraternite' => 'fraternité',
        'liberation' => 'libération', 'resistance' => 'résistance',
        'universite' => 'université', 'mediatheque' => 'médiathèque',
        'theatre' => 'théâtre', 'cimetiere' => 'cimetière',
        'marche' => 'marché', 'aerodrome' => 'aérodrome',
        'riviere' => 'rivière', 'carriere' => 'carrière', 'carrieres' => 'carrières',
        'bruyere' => 'bruyère', 'bruyeres' => 'bruyères',
        'foret' => 'forêt', 'chene' => 'chêne', 'chenes' => 'chênes',
        'frene' => 'frêne', 'frenes' => 'frênes',
        'pre' => 'pré', 'pres' => 'prés',
        'ile' => 'île', 'iles' => 'îles',
        'cote' => 'côte', 'cotes' => 'côtes',
        'vallee' => 'vallée', 'vallees' => 'vallées',
        'allee' => 'allée', 'allees' => 'allées',
        'montee' => 'montée', 'residence' => 'résidence',
        'etang' => 'étang', 'etangs' => 'étangs',
        'etoile' => 'étoile', 'epine' => 'épine', 'ecureuils' => 'écureuils',
        'coeur' => 'cœur', 'soeur' => 'sœur', 'sacre' => 'sacré',
        // titles and given names
        'general' => 'général', 'generale' => 'générale',
        'marechal' => 'maréchal', 'president' => 'président', 'abbe' => 'abbé',
        'frere' => 'frère', 'freres' => 'frères',
        'andre' => 'andré', 'rene' => 'rené', 'emile' => 'émile',
        'edouard' => 'édouard', 'eugene' => 'eugène', 'etienne' => 'étienne',
        'frederic' => 'frédéric', 'gerard' => 'gérard', 'jerome' => 'jérôme',
        'remy' => 'rémy', 'stephane' => 'stéphane', 'leon' => 'léon',
        'therese' => 'thérèse', 'helene' => 'hélène', 'irene' => 'irène',
        'aime' => 'aimé', 'desire' => 'désiré', 'felix' => 'félix',
        'cesar' => 'césar', 'clement' => 'clément', 'jaures' => 'jaurès',
        'moliere' => 'molière', 'cezanne' => 'cézanne', 'ampere' => 'ampère',
        'poincare' => 'poincaré',
    ];

    /**
     * Typographic apostrophe => plain one. Both directions are generated.
     *
     * @var array<string, string>
     */
    private const APOSTROPHES = ['’' => "'", '‘' => "'", '´' => "'"];

    public function permute(Address $address): iterable
    {
        foreach ($this->getVariations($address->street) as $street) {
            yield $address->withStreet($street);
        }

        foreach ($this->getVariations($address->city) as $city) {
            yield $address->withCity($city);
        }
    }

    /**
     * @return iterable<string>
     */
    private function getVariations(?string $value): iterable
    {
        if (null === $value) {
            return;
        }

        $variations = [];

        foreach ([$value, strtr($value, self::TO_ASCII), $this->toAccented($value)] as $candidate) {
            $variations[] = $candidate;
            $variations[] = strtr($candidate, self::APOSTROPHES);
            $variations[] = str_replace("'", '’', $candidate);
        }

        foreach (array_unique($variations) as $variation) {
            if ($variation !== $value) {
                yield $variation;
            }
        }
    }

    private function toAccented(string $value): string
    {
        return preg_replace_callback(
            '/\p{L}+/u',
            static function (array $match): string {
                $accented = self::TO_ACCENT[mb_strtolower($match[0])] ?? null;
                if (null === $accented) {
                    return $match[0];
                }

                return mb_strtoupper(mb_substr($match[0], 0, 1)) === mb_substr($match[0], 0, 1)
                    ? self::ucfirst($accented)
                    : $accented;
            },
            $value,
        ) ?? $value;
    }
}
