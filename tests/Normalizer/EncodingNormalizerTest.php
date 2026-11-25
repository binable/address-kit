<?php

declare(strict_types=1);

namespace Binable\AddressKit\Tests\Normalizer;

use Binable\AddressKit\Model\Address;
use Binable\AddressKit\Model\AddressField;
use Binable\AddressKit\Normalizer\AddressNormalizer;
use Binable\AddressKit\Normalizer\EncodingNormalizer;
use PHPUnit\Framework\TestCase;

final class EncodingNormalizerTest extends TestCase
{
    public function testAllFieldsAreCleaned(): void
    {
        $address = new Address(
            street: "\u{FEFF}Oâ€™Connell\u{00A0}Street",
            houseNumber: "１２\u{2013}１４",
            zipCode: "ＳＷ１Ａ\u{3000}１ＡＡ",
            city: "Lon\u{200B}don\r\n",
            country: ' GB ',
        );

        $normalized = EncodingNormalizer::createDefault()->normalize($address);

        self::assertTrue(
            (new Address("O'Connell Street", '12-14', 'SW1A 1AA', 'London', 'GB'))->equals($normalized),
            var_export($normalized, true),
        );
    }

    public function testEmptyAfterCleaningBecomesNull(): void
    {
        $normalized = EncodingNormalizer::createDefault()->normalize(new Address(street: "\u{200B}\u{00A0}\n", city: 'Berlin'));

        self::assertNull($normalized->street);
        self::assertSame('Berlin', $normalized->city);
    }

    public function testNormalizeString(): void
    {
        $normalizer = EncodingNormalizer::createDefault();

        self::assertSame('Müller', $normalizer->normalizeString("Mu\u{0308}ller"));
        self::assertSame('Müller', $normalizer->normalizeString('MÃ¼ller'));
        self::assertSame('12345', $normalizer->normalizeString('１２３４５', AddressField::ZipCode));
    }

    public function testRunsBeforeOtherNormalizers(): void
    {
        $normalized = AddressNormalizer::createDefault()->normalize(
            new Address("HauptstraÃŸe\u{00A0}12\u{00A0}A", null, "Ｄ－１０１１５", "M\u{00DC}NCHEN\u{200B}", "Deutsch\u{00AD}land"),
        );

        self::assertTrue(
            (new Address('Hauptstraße', '12a', '10115', 'München', 'DE'))->equals($normalized),
            var_export($normalized, true),
        );
    }
}
