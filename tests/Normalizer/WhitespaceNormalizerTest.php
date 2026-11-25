<?php

declare(strict_types=1);

namespace Binable\AddressKit\Tests\Normalizer;

use Binable\AddressKit\Model\Address;
use Binable\AddressKit\Normalizer\AddressNormalizer;
use Binable\AddressKit\Normalizer\Whitespace\BracketSpacingNormalizer;
use Binable\AddressKit\Normalizer\Whitespace\CollapseWhitespaceNormalizer;
use Binable\AddressKit\Normalizer\Whitespace\EdgePunctuationNormalizer;
use Binable\AddressKit\Normalizer\Whitespace\HyphenSpacingNormalizer;
use Binable\AddressKit\Normalizer\Whitespace\PunctuationSpacingNormalizer;
use Binable\AddressKit\Normalizer\WhitespaceNormalizer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class WhitespaceNormalizerTest extends TestCase
{
    public function testCollapse(): void
    {
        self::assertSame('Am Markt 1', (new CollapseWhitespaceNormalizer())->normalize("  Am \t  Markt\u{00A0}\u{00A0}1 "));
    }

    #[DataProvider('punctuationProvider')]
    public function testPunctuationSpacing(string $input, string $expected): void
    {
        self::assertSame($expected, (new PunctuationSpacingNormalizer())->normalize($input));
    }

    /**
     * @return iterable<array{string, string}>
     */
    public static function punctuationProvider(): iterable
    {
        yield ['Hauptstr . 1', 'Hauptstr. 1'];
        yield ['Gütersloh ,Kreis', 'Gütersloh, Kreis'];
        yield ['a;b ; c', 'a; b; c'];
        yield ['Haus (Hinten )', 'Haus (Hinten)'];
        yield ['Gebäude 1,5', 'Gebäude 1,5'];
        yield ['1,a', '1, a'];
    }

    public function testBracketSpacing(): void
    {
        self::assertSame('Hauptstr. 1 (2. OG) [links]', (new BracketSpacingNormalizer())->normalize('Hauptstr. 1 ( 2. OG ) [ links ]'));
    }

    #[DataProvider('hyphenProvider')]
    public function testHyphenSpacing(string $input, string $expected): void
    {
        self::assertSame($expected, (new HyphenSpacingNormalizer())->normalize($input));
    }

    /**
     * @return iterable<array{string, string}>
     */
    public static function hyphenProvider(): iterable
    {
        yield ['Max - Planck - Str.', 'Max-Planck-Str.'];
        yield ['12 - 14', '12-14'];
        yield ['Max -Planck', 'Max-Planck'];
        yield ['Ein- und Ausgang', 'Ein- und Ausgang'];
    }

    #[DataProvider('edgeProvider')]
    public function testEdgePunctuation(string $input, string $expected): void
    {
        self::assertSame($expected, (new EdgePunctuationNormalizer())->normalize($input));
    }

    /**
     * @return iterable<array{string, string}>
     */
    public static function edgeProvider(): iterable
    {
        yield [', Hauptstraße', 'Hauptstraße'];
        yield ['- Berlin', 'Berlin'];
        yield ['Gütersloh,', 'Gütersloh'];
        yield ['Gütersloh.', 'Gütersloh'];
        yield ['Berlin ,;', 'Berlin'];
        yield ['Hauptstr.', 'Hauptstr.'];
        yield ['Max-Planck-Str.,', 'Max-Planck-Str.'];
        yield ['Marktpl.', 'Marktpl.'];
        yield ['Am Bhf.', 'Am Bhf.'];
        yield ['Frankfurt a.M.', 'Frankfurt a.M.'];
        yield ['St.', 'St.'];
        yield ['12a.', '12a'];
        yield ["'s-Hertogenbosch", "'s-Hertogenbosch"];
        yield ['(Hinterhaus)', '(Hinterhaus)'];
    }

    public function testDefaultChain(): void
    {
        $normalizer = WhitespaceNormalizer::createDefault();

        self::assertSame('Max-Planck-Str. 1 (2. OG)', $normalizer->normalize(' ,Max - Planck - Str . 1 ( 2. OG ) ,'));
        self::assertNull($normalizer->normalize(' , . '));
        self::assertNull($normalizer->normalize(null));
    }

    public function testUsedByComponentNormalizers(): void
    {
        $normalized = AddressNormalizer::createDefault()->normalize(
            new Address(' Max - Planck - Str. ', '12 - 14 ,', ' 33330 ', 'Gütersloh,', ' DE ,'),
        );

        self::assertTrue(
            (new Address('Max-Planck-Straße', '12-14', '33330', 'Gütersloh', 'DE'))->equals($normalized),
            var_export($normalized, true),
        );
    }
}
