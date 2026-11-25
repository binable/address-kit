<?php

declare(strict_types=1);

use Binable\AddressKit\AddressKitBundle;
use Binable\AddressKit\Language\StreetLanguageMap;
use Binable\AddressKit\Normalizer\AddressNormalizer;
use Binable\AddressKit\Normalizer\CountryNormalizer;
use Binable\AddressKit\Normalizer\EncodingNormalizer;
use Binable\AddressKit\Normalizer\WhitespaceNormalizer;
use Binable\AddressKit\Permutation\AddressPermutator;
use Binable\AddressKit\Validator\Constraints\KnownStreetValidator;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\param;
use function Symfony\Component\DependencyInjection\Loader\Configurator\tagged_iterator;

return static function (ContainerConfigurator $container): void {
    $services = $container->services()
        ->defaults()
            ->autowire()
            ->autoconfigure()
            ->private();

    // Constraint validators are picked up by the validator component via the
    // "validator.constraint_validator" tag (autoconfigured).
    $services->load('Binable\\AddressKit\\Validator\\Constraints\\', '../src/Validator/Constraints/*Validator.php');

    $services->set(KnownStreetValidator::class)
        ->arg('$defaultCountry', param('address_kit.default_country'));

    $services->load('Binable\\AddressKit\\Normalizer\\', '../src/Normalizer/*Normalizer.php')
        ->exclude('../src/Normalizer/AddressNormalizer.php');

    $services->load('Binable\\AddressKit\\Normalizer\\Encoding\\', '../src/Normalizer/Encoding/*Normalizer.php');
    $services->load('Binable\\AddressKit\\Normalizer\\Whitespace\\', '../src/Normalizer/Whitespace/*Normalizer.php');
    $services->load('Binable\\AddressKit\\Normalizer\\Country\\', '../src/Normalizer/Country/*Normalizer.php');

    $services->set(EncodingNormalizer::class)
        ->arg('$normalizers', tagged_iterator(AddressKitBundle::TAG_ENCODING_NORMALIZER))
        ->public();

    $services->set(WhitespaceNormalizer::class)
        ->arg('$normalizers', tagged_iterator(AddressKitBundle::TAG_WHITESPACE_NORMALIZER))
        ->public();

    $services->set(CountryNormalizer::class)
        ->arg('$defaultCountry', param('address_kit.default_country'))
        ->arg('$normalizers', tagged_iterator(AddressKitBundle::TAG_COUNTRY_NORMALIZER))
        ->public();

    $services->set(AddressNormalizer::class)
        ->arg('$normalizers', tagged_iterator(AddressKitBundle::TAG_NORMALIZER))
        ->public();

    // Recursive: the language specific strategies live in sub namespaces named after the
    // ISO 639-1 code (Strategy\De, Strategy\Fr, …). Abstract classes are skipped.
    $services->load('Binable\\AddressKit\\Permutation\\Strategy\\', '../src/Permutation/Strategy/');

    $services->set(StreetLanguageMap::class)
        ->arg('$overrides', param('address_kit.street_languages'))
        ->public();

    $services->set(AddressPermutator::class)
        ->arg('$strategies', tagged_iterator(AddressKitBundle::TAG_PERMUTATION_STRATEGY))
        ->arg('$maxPermutations', param('address_kit.max_permutations'))
        ->public();
};
