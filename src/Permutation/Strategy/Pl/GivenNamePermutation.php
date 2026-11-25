<?php

declare(strict_types=1);

namespace Binable\AddressKit\Permutation\Strategy\Pl;

use Binable\AddressKit\Model\Address;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

/**
 * The given name in front of a surname, dropped and shortened to its initial:
 * "ul. Tadeusza Kościuszki" / "ul. T. Kościuszki" / "ul. Kościuszki".
 *
 * Together with the missing "ul." this is the most common reason a Polish
 * address does not match: the gmina files the street under the short form,
 * the resident types the long one that stands on the street sign.
 *
 * Unlike the Italian counterpart this also fires without a generic term in
 * front - "Tadeusza Kościuszki" is a complete Polish street name on its own.
 *
 * Only a known given name is touched, and it is listed in the genitive, the
 * case a Polish street name uses. Without the list "ul. Nowa Wieś" would turn
 * into "ul. Wieś".
 */
#[AsTaggedItem(priority: 30)]
final class GivenNamePermutation extends AbstractPolishPermutation
{
    /**
     * The given names Polish street names are built from, in the genitive.
     * The female ones end in "-y" or "-i" ("Marii Konopnickiej"), the male
     * ones in "-a" ("Tadeusza Kościuszki").
     *
     * @var list<string>
     */
    private const GIVEN_NAMES = [
        'adama', 'adolfa', 'albina', 'aleksandra', 'alfreda', 'andrzeja', 'antoniego', 'artura', 'augusta',
        'bartosza', 'benedykta', 'bogusława', 'bolesława', 'bronisława', 'bruna', 'cypriana', 'czesława',
        'dariusza', 'dominika', 'edmunda', 'edwarda', 'emila', 'eugeniusza', 'feliksa', 'ferdynanda',
        'floriana', 'franciszka', 'fryderyka', 'gabriela', 'grzegorza', 'gustawa', 'henryka', 'hieronima',
        'hugona', 'ignacego', 'jakuba', 'jana', 'jerzego', 'joachima', 'juliana', 'juliusza', 'kacpra',
        'kazimierza', 'konstantego', 'kornela', 'krzysztofa', 'leona', 'leopolda', 'longina', 'ludwika',
        'lucjana', 'łukasza', 'macieja', 'marcina', 'marka', 'mariana', 'mariusza', 'mieczysława',
        'michała', 'mikołaja', 'norberta', 'olgierda', 'oskara', 'pawła', 'piotra', 'przemysława',
        'rafała', 'romana', 'romualda', 'ryszarda', 'sebastiana', 'seweryna', 'stanisława', 'stefana',
        'sylwestra', 'szymona', 'tadeusza', 'tomasza', 'wacława', 'waldemara', 'wawrzyńca', 'wiktora',
        'wincentego', 'witolda', 'władysława', 'włodzimierza', 'wojciecha', 'zbigniewa', 'zdzisława',
        'zygmunta',
        // female, genitive
        'agnieszki', 'anny', 'barbary', 'bogumiły', 'cecylii', 'danuty', 'doroty', 'elizy', 'emilii',
        'eweliny', 'gabrieli', 'hanki', 'haliny', 'heleny', 'ireny', 'jadwigi', 'joanny', 'julii',
        'katarzyny', 'klaudyny', 'krystyny', 'kingi', 'ludwiki', 'małgorzaty', 'marii', 'marty',
        'narcyzy', 'natalii', 'olgi', 'stefanii', 'teresy', 'wandy', 'zofii',
    ];

    public function permute(Address $address): iterable
    {
        if (null === $address->street) {
            return;
        }

        // Polish streets carry their generic term only half of the time, so an
        // unsplittable street is treated as a name without one.
        $split = $this->splitType($address->street) ?? ['', $address->street];

        foreach ($this->getGivenNameVariations($split, self::GIVEN_NAMES) as $street) {
            if ($street !== $address->street) {
                yield $address->withStreet($street);
            }
        }
    }
}
