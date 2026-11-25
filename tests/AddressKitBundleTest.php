<?php

declare(strict_types=1);

namespace Binable\AddressKit\Tests;

use Binable\AddressKit\Model\Address;
use Binable\AddressKit\Normalizer\AddressNormalizer;
use Binable\AddressKit\Normalizer\EncodingNormalizer;
use Binable\AddressKit\Permutation\AddressPermutator;
use Binable\AddressKit\Tests\App\TestKernel;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class AddressKitBundleTest extends TestCase
{
    private TestKernel $kernel;

    protected function setUp(): void
    {
        $this->kernel = new TestKernel(['default_country' => 'AT', 'max_permutations' => 5]);
        $this->kernel->boot();
    }

    protected function tearDown(): void
    {
        $this->kernel->shutdown();
        (new Filesystem())->remove($this->kernel->getProjectDir().'/var');
    }

    public function testServicesAreWired(): void
    {
        $container = $this->kernel->getContainer();

        $normalizer = $container->get(AddressNormalizer::class);
        self::assertInstanceOf(AddressNormalizer::class, $normalizer);

        $normalized = $normalizer->normalize(new Address('Hauptstr. 1', null, '1010', 'WIEN'));
        self::assertTrue((new Address('Hauptstraße', '1', '1010', 'Wien', 'AT'))->equals($normalized));

        // encoding steps are wired via tag and run before the other normalizers
        $normalized = $normalizer->normalize(new Address("StraÃŸe\u{00A0}des 17. Juni", null, '１０１０', "Wien\u{200B}"));
        self::assertTrue((new Address('Straße des 17. Juni', null, '1010', 'Wien', 'AT'))->equals($normalized), var_export($normalized, true));

        $encoding = $container->get(EncodingNormalizer::class);
        self::assertInstanceOf(EncodingNormalizer::class, $encoding);
        self::assertSame("O'Connell", $encoding->normalizeString('Oâ€™Connell'));

        $permutator = $container->get(AddressPermutator::class);
        self::assertInstanceOf(AddressPermutator::class, $permutator);
        self::assertCount(5, $permutator->permute(new Address('Müllerstraße', '1a', '1010', 'Wien', 'AT')));
    }

    public function testValidatorUsesBundleConstraints(): void
    {
        $validator = $this->kernel->getContainer()->get('test.validator');
        self::assertInstanceOf(ValidatorInterface::class, $validator);

        self::assertCount(0, $validator->validate(new Address('Hauptstraße', '1', '1010', 'Wien', 'AT')));
        $paths = static fn (iterable $violations): array => array_map(
            static fn ($v): string => $v->getPropertyPath(),
            iterator_to_array($violations),
        );

        self::assertSame(
            ['street', 'houseNumber', 'zipCode', 'city'],
            $paths($validator->validate(new Address('<>', '#', '10115', '1', 'AT'))),
        );
        self::assertSame(['country'], $paths($validator->validate(new Address('Hauptstraße', '1', '1010', 'Wien', 'XX'))));
    }
}
