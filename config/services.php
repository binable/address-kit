<?php

declare(strict_types=1);

use Binable\AddressKit\AddressKitBundle;
use Binable\AddressKit\Normalizer\AddressNormalizer;
use Binable\AddressKit\Normalizer\CountryNormalizer;
use Binable\AddressKit\Permutation\AddressPermutator;
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

    $services->load('Binable\\AddressKit\\Normalizer\\', '../src/Normalizer/*Normalizer.php')
        ->exclude('../src/Normalizer/AddressNormalizer.php');

    $services->set(CountryNormalizer::class)
        ->arg('$defaultCountry', param('address_kit.default_country'));

    $services->set(AddressNormalizer::class)
        ->arg('$normalizers', tagged_iterator(AddressKitBundle::TAG_NORMALIZER))
        ->public();

    $services->load('Binable\\AddressKit\\Permutation\\Strategy\\', '../src/Permutation/Strategy/*');

    $services->set(AddressPermutator::class)
        ->arg('$strategies', tagged_iterator(AddressKitBundle::TAG_PERMUTATION_STRATEGY))
        ->arg('$maxPermutations', param('address_kit.max_permutations'))
        ->public();
};
