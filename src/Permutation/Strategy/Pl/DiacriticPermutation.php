<?php

declare(strict_types=1);

namespace Binable\AddressKit\Permutation\Strategy\Pl;

use Binable\AddressKit\Model\Address;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

/**
 * Folds the Polish diacritics and puts them back ("ul. Świętokrzyska" <->
 * "ul. Swietokrzyska", "Łódź" <-> "Lodz").
 *
 * Folding is applied as a whole and never rule by rule, exactly like
 * {@see \Binable\AddressKit\Permutation\Strategy\De\UmlautPermutation}: a
 * register either stores the Polish spelling or the folded one, it never mixes
 * both within one name. Many gminy run their register on a system that cannot
 * store "ł" at all, and their exports are folded top to bottom.
 *
 * The opposite direction is ambiguous - an "s" can stand for "s" and for "ś",
 * a "z" for "z", "ź" and "ż" - so it is not derived but looked up in a list of
 * the words Polish street names and towns are actually built from.
 */
#[AsTaggedItem(priority: 100)]
final class DiacriticPermutation extends AbstractPolishPermutation
{
    /**
     * Polish character => its folded spelling. This direction is never
     * ambiguous.
     *
     * @var array<string, string>
     */
    private const TO_ASCII = [
        'ą' => 'a', 'ć' => 'c', 'ę' => 'e', 'ł' => 'l', 'ń' => 'n',
        'ó' => 'o', 'ś' => 's', 'ź' => 'z', 'ż' => 'z',
        'Ą' => 'A', 'Ć' => 'C', 'Ę' => 'E', 'Ł' => 'L', 'Ń' => 'N',
        'Ó' => 'O', 'Ś' => 'S', 'Ź' => 'Z', 'Ż' => 'Z',
    ];

    /**
     * Folded word => Polish word. Only the words that really turn up in a
     * street name or as a town are listed - deriving the diacritics would turn
     * "Polna" into "Pólna" and "Zielona" into "Ziełona".
     *
     * @var array<string, string>
     */
    private const TO_POLISH = [
        // the generic terms and titles
        'wybrzeze' => 'wybrzeże', 'pasaz' => 'pasaż',
        'swietego' => 'świętego', 'swietej' => 'świętej', 'swietych' => 'świętych',
        'sw' => 'św', 'generala' => 'generała', 'marszalka' => 'marszałka',
        'pulkownika' => 'pułkownika', 'podpulkownika' => 'podpułkownika',
        'ksiedza' => 'księdza', 'kardynala' => 'kardynała', 'krolowej' => 'królowej',
        'inzyniera' => 'inżyniera', 'blogoslawionego' => 'błogosławionego',
        'blogoslawionej' => 'błogosławionej', 'plk' => 'płk', 'inz' => 'inż',
        'bl' => 'bł',
        // the most common street names
        'dluga' => 'długa', 'krotka' => 'krótka', 'waska' => 'wąska',
        'glowna' => 'główna', 'polnocna' => 'północna',
        'poludniowa' => 'południowa', 'lesna' => 'leśna', 'lakowa' => 'łąkowa',
        'sloneczna' => 'słoneczna', 'ksiezycowa' => 'księżycowa',
        'koscielna' => 'kościelna', 'kosciuszki' => 'kościuszki',
        'zrodlana' => 'źródlana', 'zwirowa' => 'żwirowa', 'zwirki' => 'żwirki',
        'debowa' => 'dębowa', 'swierkowa' => 'świerkowa', 'wisniowa' => 'wiśniowa',
        'jasminowa' => 'jaśminowa', 'rozana' => 'różana', 'mlynska' => 'młyńska',
        'strazacka' => 'strażacka', 'przemyslowa' => 'przemysłowa',
        'spoldzielcza' => 'spółdzielcza', 'wolnosci' => 'wolności',
        'niepodleglosci' => 'niepodległości', 'zwyciestwa' => 'zwycięstwa',
        'partyzantow' => 'partyzantów', 'powstancow' => 'powstańców',
        'zolnierzy' => 'żołnierzy', 'legionow' => 'legionów',
        'swietokrzyska' => 'świętokrzyska', 'marszalkowska' => 'marszałkowska',
        'pulawska' => 'puławska', 'grojecka' => 'grójecka', 'zabia' => 'żabia',
        'piekna' => 'piękna', 'chlodna' => 'chłodna',
        'pilsudskiego' => 'piłsudskiego',
        'slowackiego' => 'słowackiego', 'zeromskiego' => 'żeromskiego',
        'wyspianskiego' => 'wyspiańskiego', 'popieluszki' => 'popiełuszki',
        'jagiellonska' => 'jagiellońska', 'lokietka' => 'łokietka',
        'sniadeckich' => 'śniadeckich',
        // the towns and the adjectives derived from them
        'lodz' => 'łódź', 'lodzka' => 'łódzka', 'gdansk' => 'gdańsk',
        'gdanska' => 'gdańska', 'poznan' => 'poznań', 'poznanska' => 'poznańska',
        'wroclaw' => 'wrocław', 'wroclawska' => 'wrocławska', 'torun' => 'toruń',
        'torunska' => 'toruńska', 'krakow' => 'kraków', 'czestochowa' => 'częstochowa',
        'czestochowska' => 'częstochowska', 'bialystok' => 'białystok',
        'bialostocka' => 'białostocka', 'rzeszow' => 'rzeszów', 'plock' => 'płock',
        'wloclawek' => 'włocławek', 'walbrzych' => 'wałbrzych',
        'przemysl' => 'przemyśl', 'chelm' => 'chełm', 'zamosc' => 'zamość',
        'swidnica' => 'świdnica', 'tarnow' => 'tarnów', 'oswiecim' => 'oświęcim',
        'raciborz' => 'racibórz', 'olsztynska' => 'olsztyńska',
        'szczecinska' => 'szczecińska', 'zielonogorska' => 'zielonogórska',
        'slaska' => 'śląska', 'slask' => 'śląsk', 'gora' => 'góra', 'sacz' => 'sącz',
    ];

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

        foreach ([strtr($value, self::TO_ASCII), $this->toPolish($value)] as $variation) {
            if ($variation !== $value) {
                yield $variation;
            }
        }
    }

    private function toPolish(string $value): string
    {
        return preg_replace_callback(
            '/\p{L}+/u',
            static function (array $match): string {
                $polish = self::TO_POLISH[mb_strtolower($match[0])] ?? null;
                if (null === $polish) {
                    return $match[0];
                }

                return mb_strtoupper(mb_substr($match[0], 0, 1)) === mb_substr($match[0], 0, 1)
                    ? self::ucfirst($polish)
                    : $polish;
            },
            $value,
        ) ?? $value;
    }
}
