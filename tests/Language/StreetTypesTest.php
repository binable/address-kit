<?php

declare(strict_types=1);

namespace Binable\AddressKit\Tests\Language;

use Binable\AddressKit\Language;
use Binable\AddressKit\Language\AbstractStreetTypes;
use Binable\AddressKit\Language\En\UspsAbbreviations;
use Binable\AddressKit\Permutation\Strategy\En\AbstractEnglishPermutation;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class StreetTypesTest extends TestCase
{
    /**
     * @return iterable<string, array{class-string<AbstractStreetTypes>}>
     */
    public static function dictionaryProvider(): iterable
    {
        yield 'cs' => [Language\Cs\StreetTypes::class];
        yield 'en' => [Language\En\StreetTypes::class];
        yield 'fr' => [Language\Fr\StreetTypes::class];
        yield 'it' => [Language\It\StreetTypes::class];
        yield 'lt' => [Language\Lt\StreetTypes::class];
        yield 'lv' => [Language\Lv\StreetTypes::class];
        yield 'pl' => [Language\Pl\StreetTypes::class];
        yield 'pt' => [Language\Pt\StreetTypes::class];
        yield 'ro' => [Language\Ro\StreetTypes::class];
    }

    /**
     * Every spelling is lower case, the canonical term comes first, and no
     * spelling belongs to two terms - otherwise the lookups would depend on
     * the order of the table.
     *
     * @param class-string<AbstractStreetTypes> $dictionary
     */
    #[DataProvider('dictionaryProvider')]
    public function testTablesAreConsistent(string $dictionary): void
    {
        $seen = [];
        foreach ($dictionary::getTypes(true) as $canonical => $spellings) {
            self::assertSame($canonical, $spellings[0], $canonical);

            foreach ($spellings as $spelling) {
                self::assertSame(mb_strtolower($spelling), $spelling, $spelling);

                $folded = rtrim($spelling, '.');
                self::assertTrue(!isset($seen[$folded]) || $seen[$folded] === $canonical, \sprintf('"%s" is a spelling of "%s" and "%s"', $spelling, $seen[$folded] ?? '', $canonical));
                $seen[$folded] = $canonical;
            }
        }

        self::assertSame([], array_intersect_key($dictionary::TYPES, $dictionary::NAME_LIKE_TYPES));
    }

    public function testLookups(): void
    {
        self::assertSame('avenue', Language\Fr\StreetTypes::getCanonical('AV.'));
        self::assertSame('avenue', Language\Fr\StreetTypes::getCanonical('Av'));
        self::assertNull(Language\Fr\StreetTypes::getCanonical('clos'));
        self::assertSame('clos', Language\Fr\StreetTypes::getCanonical('Clos', true));
        self::assertNull(Language\Fr\StreetTypes::getCanonical(''));

        self::assertSame(['praça', 'praca', 'pç.', 'pc.', 'pc', 'pça', 'pca'], Language\Pt\StreetTypes::getSpellings('praca'));
        self::assertSame([], Language\Pt\StreetTypes::getSpellings('Liberdade'));

        self::assertTrue(Language\En\StreetTypes::isType('Cres'));
        self::assertFalse(Language\En\StreetTypes::isType('Park'));
        self::assertTrue(Language\En\StreetTypes::isType('Park', true));

        self::assertContains('g.', Language\Lt\StreetTypes::getAllSpellings());
        self::assertNotContains('parkas', Language\Lt\StreetTypes::getAllSpellings());
        self::assertContains('parkas', Language\Lt\StreetTypes::getAllSpellings(true));
    }

    public function testEnglishPermutationConstantsStayAvailable(): void
    {
        self::assertSame(UspsAbbreviations::SUFFIXES, AbstractEnglishPermutation::SUFFIXES);
        self::assertSame(UspsAbbreviations::DIRECTIONALS, AbstractEnglishPermutation::DIRECTIONALS);
        self::assertSame(Language\En\Ordinals::SPELLED_OUT, AbstractEnglishPermutation::ORDINALS);
        self::assertSame('Blvd', UspsAbbreviations::SUFFIXES['Boulevard']);
    }
}
