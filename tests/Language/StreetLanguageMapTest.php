<?php

declare(strict_types=1);

namespace Binable\AddressKit\Tests\Language;

use Binable\AddressKit\Language\StreetLanguageMap;
use PHPUnit\Framework\TestCase;

final class StreetLanguageMapTest extends TestCase
{
    public function testDefaults(): void
    {
        $map = new StreetLanguageMap();

        self::assertSame(['de'], $map->getLanguages('DE'));
        self::assertSame(['de'], $map->getLanguages(' de '));
        self::assertSame(['de', 'fr', 'it'], $map->getLanguages('CH'));
        self::assertSame([], $map->getLanguages('XX'));
        self::assertSame([], $map->getLanguages(null));
        self::assertSame(['AT', 'CH', 'DE', 'LI'], $map->getCountries('de'));
    }

    public function testOverridesReplaceTheCountryEntry(): void
    {
        $map = new StreetLanguageMap(['be' => ['FR', 'nl', 'nl'], 'DE' => [], 'ES' => ['es']]);

        self::assertSame(['fr', 'nl'], $map->getLanguages('BE'));
        self::assertSame([], $map->getLanguages('DE'));
        self::assertSame(['es'], $map->getLanguages('ES'));
        self::assertSame(['de'], $map->getLanguages('AT'));
    }
}
