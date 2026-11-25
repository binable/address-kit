<?php

declare(strict_types=1);

namespace Binable\AddressKit\Tests\Permutation\Strategy;

use Binable\AddressKit\Model\Address;
use Binable\AddressKit\Permutation\LocalizedPermutationStrategyInterface;
use Binable\AddressKit\Permutation\PermutationStrategyInterface;
use Binable\AddressKit\Permutation\Strategy;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class StrategyTest extends TestCase
{
    /**
     * @return iterable<string, array{PermutationStrategyInterface, string, list<string>}>
     */
    public static function streetProvider(): iterable
    {
        // language independent
        yield 'house number in street' => [new Strategy\HouseNumberInStreetPermutation(), 'Denkstr. 19', ['Denkstr.']];
        yield 'case' => [new Strategy\CasePermutation(), 'HAUPTSTRASSE', ['Hauptstrasse']];
        yield 'case keeps particles' => [new Strategy\CasePermutation(), 'straße im moos', ['Straße im Moos']];

        // de
        yield 'de umlaut' => [new Strategy\De\UmlautPermutation(), 'Müllerstraße', ['Muellerstraße']];
        yield 'de umlaut back' => [new Strategy\De\UmlautPermutation(), 'Muellerweg', ['Müllerweg']];
        yield 'de hyphen' => [new Strategy\De\HyphenPermutation(), 'Albert-Einstein-Straße', ['Albert Einstein Straße']];
        yield 'de hyphen camel case' => [new Strategy\De\HyphenPermutation(), 'TheoNeubauerStr', ['Theo-Neubauer-Str']];
        yield 'de preposition dropped' => [new Strategy\De\PrepositionPermutation(), 'Am Freiheitsplatz', ['Freiheitsplatz']];
        yield 'de preposition added' => [new Strategy\De\PrepositionPermutation(), 'Alte Mühle', ['An der Alten Mühle']];
        yield 'de grundwort' => [new Strategy\De\GrundwortPermutation(), 'Marktplatz', ['Marktplatz', 'Marktpl.', 'Markt Platz', 'Markt Pl.']];
        yield 'de straße' => [new Strategy\De\StrassePermutation(), 'Hauptstr.', ['Hauptstraße', 'Hauptstrasse', 'Hauptstr.', 'Hauptstr', 'Haupt Straße', 'Haupt Strasse', 'Haupt Str.']];
        yield 'de straße missing' => [new Strategy\De\StrassePermutation(), 'Leipziger', ['Leipziger Straße', 'Leipziger Strasse', 'Leipziger Str.']];

        // nl
        yield 'nl street' => [new Strategy\Nl\StreetPermutation(), 'Kerkstraat', ['Kerkstr.', 'Kerkstr']];
        yield 'nl particles' => [new Strategy\Nl\StreetPermutation(), 'Burgemeester van der Vlugtstraat', ['Burg. v.d. Vlugtstraat', 'Burg. v.d. Vlugtstr.']];

        // en
        yield 'en suffix' => [new Strategy\En\StreetSuffixPermutation(), 'Sunset Boulevard', ['Sunset Blvd', 'Sunset']];
        yield 'en directional' => [new Strategy\En\DirectionalPermutation(), 'W Sunset Blvd', ['West Sunset Blvd', 'Sunset Blvd']];
        yield 'en ordinal' => [new Strategy\En\OrdinalPermutation(), '2nd Ave', ['Second Ave']];

        // fr
        yield 'fr type' => [new Strategy\Fr\StreetTypePermutation(), 'Av. de la Gare', ['Avenue de la Gare', 'Av de la Gare', 'Ave de la Gare']];
        yield 'fr accent' => [new Strategy\Fr\AccentPermutation(), 'Rue de l’Église', ["Rue de l'Église", 'Rue de l’Eglise']];
        yield 'fr hyphen' => [new Strategy\Fr\HyphenPermutation(), 'Avenue du Général de Gaulle', ['Avenue du Général-de-Gaulle']];
        yield 'fr title' => [new Strategy\Fr\TitlePermutation(), 'Rue Saint-Étienne', ['Rue St-Étienne']];

        // it
        yield 'it type' => [new Strategy\It\StreetTypePermutation(), 'P.zza Garibaldi', ['Piazza Garibaldi', 'Pza Garibaldi']];
        yield 'it given name' => [new Strategy\It\GivenNamePermutation(), 'Via Guglielmo Marconi', ['Via Marconi', 'Via G. Marconi']];
        yield 'it saint' => [new Strategy\It\TitlePermutation(), "Piazza Sant'Agostino", ['Piazza S. Agostino', 'Piazza Sant’Agostino']];
        yield 'it accent' => [new Strategy\It\AccentPermutation(), "VIA DELLA LIBERTA'", ['VIA DELLA LIBERTÀ', 'VIA DELLA LIBERTA']];

        // pl
        yield 'pl type' => [new Strategy\Pl\StreetTypePermutation(), 'ul. Długa', ['Długa', 'ulica Długa', 'ul Długa']];
        yield 'pl type implied' => [new Strategy\Pl\StreetTypePermutation(), 'Długa', ['ul. Długa']];
        yield 'pl given name' => [new Strategy\Pl\GivenNamePermutation(), 'ul. Tadeusza Kościuszki', ['ul. Kościuszki', 'ul. T. Kościuszki']];
        yield 'pl title' => [new Strategy\Pl\TitlePermutation(), 'ul. Generała Andersa', ['ul. Gen. Andersa']];
        yield 'pl diacritic' => [new Strategy\Pl\DiacriticPermutation(), 'ul. Swietokrzyska', ['ul. Świętokrzyska']];

        // da
        yield 'da letter' => [new Strategy\Da\LetterPermutation(), 'Nørregade', ['Noerregade', 'Norregade']];
        yield 'da accent' => [new Strategy\Da\AccentPermutation(), 'H.C. Andersens Alle', ['H.C. Andersens Allé']];
        yield 'da prefix' => [new Strategy\Da\PrefixPermutation(), 'Gammel Kongevej', ['Gl. Kongevej']];
        yield 'da term' => [new Strategy\Da\StreetTermPermutation(), 'Skovvej', ['Skovvejen']];
    }

    /**
     * @param list<string> $expected
     */
    #[DataProvider('streetProvider')]
    public function testStreetVariants(PermutationStrategyInterface $strategy, string $street, array $expected): void
    {
        $streets = [];
        foreach ($strategy->permute(new Address($street, null, null, null, null)) as $variant) {
            $streets[] = $variant->street;
        }

        foreach ($expected as $variant) {
            self::assertContains($variant, $streets, \sprintf('Variants of "%s": %s', $street, implode(' | ', array_map(strval(...), $streets))));
        }
    }

    public function testHouseNumberIsSplitOff(): void
    {
        $variants = iterator_to_array((new Strategy\HouseNumberInStreetPermutation())->permute(new Address('Via Roma 12/B')), false);

        self::assertCount(2, $variants);
        self::assertSame('Via Roma', $variants[1]->street);
        self::assertSame('12/B', $variants[1]->houseNumber);
    }

    public function testCityIsPermuted(): void
    {
        $cities = array_map(
            static fn (Address $a): ?string => $a->city,
            iterator_to_array((new Strategy\Pl\DiacriticPermutation())->permute(new Address(null, null, null, 'Łódź')), false),
        );

        self::assertSame(['Lodz'], $cities);
    }

    public function testNullStreetIsIgnored(): void
    {
        foreach (self::streetProvider() as [$strategy]) {
            self::assertSame([], iterator_to_array($strategy->permute(new Address()), false), $strategy::class);
        }
    }

    public function testLanguages(): void
    {
        $languages = [];
        foreach (self::streetProvider() as [$strategy]) {
            $languages[$strategy::class] = $strategy instanceof LocalizedPermutationStrategyInterface ? $strategy->getLanguage() : null;
        }

        self::assertNull($languages[Strategy\CasePermutation::class]);
        self::assertNull($languages[Strategy\HouseNumberInStreetPermutation::class]);
        self::assertSame('de', $languages[Strategy\De\StrassePermutation::class]);
        self::assertSame('nl', $languages[Strategy\Nl\StreetPermutation::class]);
        self::assertSame('en', $languages[Strategy\En\StreetSuffixPermutation::class]);
        self::assertSame('fr', $languages[Strategy\Fr\StreetTypePermutation::class]);
        self::assertSame('it', $languages[Strategy\It\StreetTypePermutation::class]);
        self::assertSame('pl', $languages[Strategy\Pl\StreetTypePermutation::class]);
        self::assertSame('da', $languages[Strategy\Da\StreetTermPermutation::class]);
    }
}
