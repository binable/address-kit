<?php

declare(strict_types=1);

namespace Binable\AddressKit\Tests\Matching;

use Binable\AddressKit\Matching\StreetTypo;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

final class StreetTypoTest extends TestCase
{
    private const REGISTER = ['Paul-Gerhardt-Allee', 'Bürgermeister-Spitta-Allee', 'Müllerstraße', 'Hof', 'Hohe'];

    #[TestWith(['Paul-Gerhard-Allee', 'Paul-Gerhardt-Allee'])]
    #[TestWith(['Bürgermeister-Spitta-Alle', 'Bürgermeister-Spitta-Allee'])]
    #[TestWith(['paul-gerhardt-alle', 'Paul-Gerhardt-Allee'])]
    #[TestWith(['Mullerstraße', 'Müllerstraße'])]
    public function testFindsUniqueTypo(string $typed, string $expected): void
    {
        self::assertSame($expected, StreetTypo::findUnique([$typed], self::REGISTER));
    }

    #[TestWith(['Hoh'])]
    #[TestWith(['Paul-Gerhardt-Allee'])]
    #[TestWith(['Paul-Gerhrd-Alle'])]
    public function testNoHit(string $typed): void
    {
        self::assertNull(StreetTypo::findUnique([$typed], self::REGISTER));
    }

    public function testAmbiguousHitIsIgnored(): void
    {
        self::assertNull(StreetTypo::findUnique(['Lindenweg'], ['Lindenwek', 'Lindenwef']));
    }

    public function testAllTriedSpellingsCount(): void
    {
        self::assertSame('Müllerstraße', StreetTypo::findUnique(['Mülerstr.', 'Mülerstraße'], self::REGISTER));
    }

    public function testDistanceCountsCharacters(): void
    {
        self::assertSame(1, StreetTypo::distance('müller', 'muller'));
        self::assertSame(0, StreetTypo::distance('straße', 'straße'));
        self::assertSame(2, StreetTypo::distance('straße', 'strasse'));
    }
}
