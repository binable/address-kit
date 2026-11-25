<?php

declare(strict_types=1);

namespace Binable\AddressKit\Tests\Normalizer\Encoding;

use Binable\AddressKit\Model\AddressField;
use Binable\AddressKit\Normalizer\Encoding\ApostropheNormalizer;
use Binable\AddressKit\Normalizer\Encoding\ControlCharacterNormalizer;
use Binable\AddressKit\Normalizer\Encoding\DashNormalizer;
use Binable\AddressKit\Normalizer\Encoding\FullwidthNormalizer;
use Binable\AddressKit\Normalizer\Encoding\InvisibleCharacterNormalizer;
use Binable\AddressKit\Normalizer\Encoding\MojibakeNormalizer;
use Binable\AddressKit\Normalizer\Encoding\UnicodeNfcNormalizer;
use Binable\AddressKit\Normalizer\Encoding\Utf8Normalizer;
use Binable\AddressKit\Normalizer\Encoding\SpaceNormalizer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class StringNormalizerTest extends TestCase
{
    public function testUtf8(): void
    {
        $normalizer = new Utf8Normalizer();

        self::assertSame('Müller', $normalizer->normalize("M\xFCller"));       // ISO-8859-1
        self::assertSame('Grüße – 5 €', $normalizer->normalize("Gr\xFC\xDFe \x96 5 \x80")); // Windows-1252
        self::assertSame('Müller', $normalizer->normalize('Müller'));
    }

    #[DataProvider('mojibakeProvider')]
    public function testMojibake(string $input, string $expected): void
    {
        self::assertSame($expected, (new MojibakeNormalizer())->normalize($input));
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function mojibakeProvider(): iterable
    {
        yield 'umlaut' => ['MÃ¼ller', 'Müller'];
        yield 'accent' => ['CafÃ©', 'Café'];
        yield 'eszett' => ['HauptstraÃŸe', 'Hauptstraße'];
        yield 'capital umlaut' => ['Ã–sterreich', 'Österreich'];
        yield 'quote' => ['Oâ€™Connell', 'O’Connell'];
        yield 'mixed' => ['Müller-StraÃŸe', 'Müller-Straße'];
        yield 'double encoded' => ['MÃƒÂ¼ller', 'Müller'];
        yield 'correct stays' => ['Müller', 'Müller'];
        yield 'correct accents stay' => ['Saint-Étienne', 'Saint-Étienne'];
        yield 'non latin stays' => ['東京都', '東京都'];
        yield 'ascii stays' => ['Main Street', 'Main Street'];
    }

    public function testNfc(): void
    {
        $decomposed = "Mu\u{0308}ller";
        $normalized = (new UnicodeNfcNormalizer())->normalize($decomposed);

        self::assertSame('Müller', $normalized);
        self::assertSame(6, mb_strlen($normalized));
    }

    public function testInvisibleCharacters(): void
    {
        self::assertSame(
            'Hauptstraße',
            (new InvisibleCharacterNormalizer())->normalize("\u{FEFF}Haupt\u{00AD}stra\u{200B}ße\u{200E}\u{200D}\u{200F}"),
        );
    }

    public function testControlCharacters(): void
    {
        self::assertSame(
            'Haupt straße 12 a b',
            (new ControlCharacterNormalizer())->normalize("Haupt\r\nstraße\t12\u{2028}a\x00b"),
        );
    }

    public function testSpaces(): void
    {
        self::assertSame(
            'Frankfurt am Main 1',
            (new SpaceNormalizer())->normalize("  Frankfurt\u{00A0}am\u{202F}\u{2009}Main\u{3000}\t 1 "),
        );
    }

    public function testDashes(): void
    {
        self::assertSame('12-14 -- - - -', (new DashNormalizer())->normalize("12\u{2013}14 \u{2014}\u{2212} \u{2010} \u{2011} \u{FF0D}"));
    }

    public function testApostrophes(): void
    {
        $normalizer = new ApostropheNormalizer();

        self::assertSame("O'Connell Street", $normalizer->normalize('O’Connell Street'));
        self::assertSame("Rue d'Alésia", $normalizer->normalize('Rue d´Alésia'));
        self::assertSame("L'Aquila 'x'", $normalizer->normalize('L`Aquila ‘x’'));
    }

    public function testFullwidth(): void
    {
        $normalizer = new FullwidthNormalizer();

        // digits only for other fields, "½" and fullwidth letters stay
        self::assertSame('123 Ａ ½', $normalizer->normalize('１２３ Ａ ½', AddressField::HouseNumber));
        self::assertSame('Ｔｏｋｙｏ 1', $normalizer->normalize('Ｔｏｋｙｏ １'));

        // full NFKC for the ZIP code
        self::assertSame('100-0001', $normalizer->normalize('１００－０００１', AddressField::ZipCode));
        self::assertSame('SW1A 1AA', $normalizer->normalize('ＳＷ１Ａ　１ＡＡ', AddressField::ZipCode));
    }

    public function testFullwidthNfkcFieldsConfigurable(): void
    {
        $normalizer = new FullwidthNormalizer([AddressField::ZipCode, AddressField::HouseNumber]);

        self::assertSame('12A', $normalizer->normalize('１２Ａ', AddressField::HouseNumber));
    }
}
