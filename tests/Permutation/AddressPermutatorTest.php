<?php

declare(strict_types=1);

namespace Binable\AddressKit\Tests\Permutation;

use Binable\AddressKit\Language\StreetLanguageMap;
use Binable\AddressKit\Model\Address;
use Binable\AddressKit\Permutation\AddressPermutator;
use PHPUnit\Framework\TestCase;

final class AddressPermutatorTest extends TestCase
{
    public function testOriginalComesFirstAndVariantsAreCombined(): void
    {
        $address = new Address('Müllerstraße', '12a', '10115', 'Berlin', 'DE');
        $variants = AddressPermutator::createDefault()->permute($address);

        self::assertSame($address, $variants[0]);

        $streets = self::streets($variants);
        self::assertContains('Müllerstr.', $streets);
        self::assertContains('Müller Straße', $streets);
        self::assertContains('Muellerstrasse', $streets);
        self::assertContains('Muellerstr.', $streets);

        $houseNumbers = array_unique(array_map(static fn (Address $a): ?string => $a->houseNumber, $variants));
        self::assertEqualsCanonicalizing(['12a', '12 a', '12A', '12 A'], array_values($houseNumbers));
    }

    public function testVariantsAreUnique(): void
    {
        $variants = AddressPermutator::createDefault()->permute(new Address('Hauptstraße', '1a', '10115', 'Köln', 'DE'));
        $keys = array_map(static fn (Address $a): string => serialize($a), $variants);

        self::assertSame($keys, array_values(array_unique($keys)));
    }

    public function testLimit(): void
    {
        $variants = AddressPermutator::createDefault(3)->permute(new Address('Müllerstraße', '12a', '10115', 'Berlin', 'DE'));

        self::assertCount(3, $variants);
    }

    public function testNothingToPermute(): void
    {
        $address = new Address('Am Markt', '1', '10115', 'Berlin', null);

        self::assertSame([$address], AddressPermutator::createDefault()->permute($address));
    }

    public function testOnlyTheLanguagesOfTheCountryAreUsed(): void
    {
        $permutator = AddressPermutator::createDefault();

        // German strategies for DE …
        self::assertContains('Hauptstr.', self::streets($permutator->permute(new Address('Hauptstraße', null, null, null, 'DE'))));
        self::assertNotContains('Avenue de la Gare', self::streets($permutator->permute(new Address('Av. de la Gare', null, null, null, 'DE'))));

        // … French ones for FR …
        self::assertContains('Avenue de la Gare', self::streets($permutator->permute(new Address('Av. de la Gare', null, null, null, 'FR'))));
        self::assertNotContains('Hauptstr.', self::streets($permutator->permute(new Address('Hauptstraße', null, null, null, 'FR'))));

        // … both for CH
        self::assertContains('Hauptstr.', self::streets($permutator->permute(new Address('Hauptstraße', null, null, null, 'CH'))));
        self::assertContains('Avenue de la Gare', self::streets($permutator->permute(new Address('Av. de la Gare', null, null, null, 'CH'))));

        // language independent strategies always run
        self::assertContains('Hauptstraße', self::streets($permutator->permute(new Address('Hauptstraße 5', null, null, null, 'XX'))));
    }

    public function testLanguagesCanBeGivenExplicitly(): void
    {
        $streets = self::streets(AddressPermutator::createDefault()->permute(new Address('Hauptstraße', null, null, null, 'FR'), ['de']));

        self::assertContains('Hauptstr.', $streets);
    }

    public function testLanguageMapOverride(): void
    {
        $permutator = AddressPermutator::createDefault(languageMap: new StreetLanguageMap(['BE' => ['nl']]));

        self::assertContains('Kerkstr.', self::streets($permutator->permute(new Address('Kerkstraat', null, null, null, 'BE'))));
    }

    /**
     * @param list<Address> $variants
     *
     * @return list<string|null>
     */
    private static function streets(array $variants): array
    {
        return array_values(array_unique(array_map(static fn (Address $a): ?string => $a->street, $variants)));
    }
}
