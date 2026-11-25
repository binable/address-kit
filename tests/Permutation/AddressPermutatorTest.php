<?php

declare(strict_types=1);

namespace Binable\AddressKit\Tests\Permutation;

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

        $streets = array_unique(array_map(static fn (Address $a): ?string => $a->street, $variants));
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
        $address = new Address('Am Markt', '1', '10115', 'Berlin', 'DE');

        self::assertSame([$address], AddressPermutator::createDefault()->permute($address));
    }
}
