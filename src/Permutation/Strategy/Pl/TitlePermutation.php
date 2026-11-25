<?php

declare(strict_types=1);

namespace Binable\AddressKit\Permutation\Strategy\Pl;

use Binable\AddressKit\Model\Address;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

/**
 * The titles inside the name part, in both spellings: "ul. Świętego Marcina" /
 * "ul. św. Marcina", "ul. Generała Andersa" / "ul. gen. Andersa",
 * "ul. Księdza Popiełuszki" / "ul. ks. Popiełuszki".
 *
 * Polish titles stand in the genitive and are abbreviated far more often than
 * they are written out - a register that stores "gen." and an address that
 * says "Generała" never meet.
 *
 * Only the genitive forms are listed: a street is always named *after*
 * somebody ("ulica Generała Andersa"), the nominative "Generał" does not turn
 * up in a street name.
 */
#[AsTaggedItem(priority: 20)]
final class TitlePermutation extends AbstractPolishPermutation
{
    /**
     * Full spelling => abbreviation. Both directions are generated, several
     * spellings of the same title are listed separately.
     *
     * "o." (ojca) and "b." are missing on purpose: a single letter with a dot
     * is an initial ("ul. T. Kościuszki") far more often than a title.
     *
     * @var list<array{string, string}>
     */
    private const ABBREVIATIONS = [
        ['błogosławionego', 'bł.'],
        ['błogosławionej', 'bł.'],
        ['arcybiskupa', 'abp.'],
        ['arcybiskupa', 'abpa'],
        ['świętego', 'św.'],
        ['świętej', 'św.'],
        ['świętych', 'św.'],
        ['generała', 'gen.'],
        ['marszałka', 'marsz.'],
        ['pułkownika', 'płk'],
        ['podpułkownika', 'ppłk'],
        ['porucznika', 'por.'],
        ['podporucznika', 'ppor.'],
        ['kapitana', 'kpt.'],
        ['majora', 'mjr'],
        ['hetmana', 'hetm.'],
        ['księdza', 'ks.'],
        ['kardynała', 'kard.'],
        ['biskupa', 'bp.'],
        ['biskupa', 'bpa'],
        ['prymasa', 'prym.'],
        ['profesora', 'prof.'],
        ['doktora', 'dr.'],
        ['doktora', 'dra'],
        ['inżyniera', 'inż.'],
    ];

    public function permute(Address $address): iterable
    {
        if (null === $address->street) {
            return;
        }

        foreach ($this->getPairVariations($address->street, self::ABBREVIATIONS) as $street) {
            yield $address->withStreet($street);
        }
    }
}
