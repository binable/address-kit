<?php

declare(strict_types=1);

namespace Binable\AddressKit\Tests\Normalizer;

use Binable\AddressKit\Normalizer\StreetKey;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

final class StreetKeyTest extends TestCase
{
    #[TestWith(['Hauptstraße', 'hauptstr.'])]
    #[TestWith(['Hauptstrasse', 'hauptstr.'])]
    #[TestWith(['Hauptstr.', 'hauptstr.'])]
    #[TestWith(['Hauptstr', 'hauptstr.'])]
    #[TestWith(['HAUPTSTRASSE', 'hauptstr.'])]
    #[TestWith(['HAUPTSTRAẞE', 'hauptstr.'])]
    #[TestWith(['Hauptstrase', 'hauptstr.'])]
    #[TestWith(['Weberstasse', 'weberstr.'])]
    #[TestWith(['Cyriakstr..', 'cyriakstr.'])]
    #[TestWith(['Haupt Straße', 'haupt str.'])]
    #[TestWith(['Albert-Einstein-Straße', 'albert-einstein-str.'])]
    #[TestWith(['Albert-Einstein-Str.', 'albert-einstein-str.'])]
    #[TestWith(['Straße des 17. Juni', 'str. des 17. juni'])]
    #[TestWith(['Str. des 17. Juni', 'str. des 17. juni'])]
    #[TestWith(['Müllerweg', 'muellerweg'])]
    #[TestWith(['Muellerweg', 'muellerweg'])]
    #[TestWith(['Ölweg', 'oelweg'])]
    #[TestWith(['ÜBER DEN ÄCKERN', 'ueber den aeckern'])]
    #[TestWith(['Schloßstraße', 'schlossstr.'])]
    #[TestWith(['Straßenbahnweg', 'strassenbahnweg'])]
    #[TestWith(['Am Strassenbahndepot', 'am strassenbahndepot'])]
    #[TestWith(['Bahnhofstr./Ecke Ring', 'bahnhofstr./ecke ring'])]
    #[TestWith(["Am\u{00A0}Markt", 'am markt'])]
    #[TestWith(['  Am   Markt  ', 'am markt'])]
    #[TestWith(['Hauptstraße,', 'hauptstr.'])]
    #[TestWith(['Am Markt', 'am markt'])]
    #[TestWith(['', ''])]
    public function testCreate(string $street, string $expected): void
    {
        self::assertSame($expected, StreetKey::create($street));
    }

    public function testSpellingsShareOneKey(): void
    {
        $keys = array_unique(array_map(
            StreetKey::create(...),
            ['Görlitzer Straße', 'Goerlitzer Strasse', 'Görlitzer Str.', 'GOERLITZER STR', "Görlitzer\u{00A0}Straße"],
        ));

        self::assertSame(['goerlitzer str.'], array_values($keys));
    }
}
