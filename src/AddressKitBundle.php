<?php

declare(strict_types=1);

namespace Binable\AddressKit;

use Binable\AddressKit\Normalizer\ComponentNormalizerInterface;
use Binable\AddressKit\Normalizer\Country\CountryCodeNormalizerInterface;
use Binable\AddressKit\Normalizer\Encoding\EncodingNormalizerInterface;
use Binable\AddressKit\Normalizer\Whitespace\WhitespaceNormalizerInterface;
use Binable\AddressKit\Normalizer\ZipCode\CountryZipCodeNormalizerInterface;
use Binable\AddressKit\Permutation\PermutationStrategyInterface;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

final class AddressKitBundle extends AbstractBundle
{
    public const TAG_NORMALIZER = 'address_kit.normalizer';
    public const TAG_ENCODING_NORMALIZER = 'address_kit.encoding_normalizer';
    public const TAG_WHITESPACE_NORMALIZER = 'address_kit.whitespace_normalizer';
    public const TAG_COUNTRY_NORMALIZER = 'address_kit.country_normalizer';
    public const TAG_ZIP_CODE_NORMALIZER = 'address_kit.zip_code_normalizer';
    public const TAG_PERMUTATION_STRATEGY = 'address_kit.permutation_strategy';

    protected string $extensionAlias = 'address_kit';

    public function configure(DefinitionConfigurator $definition): void
    {
        $definition->rootNode()
            ->children()
                ->scalarNode('default_country')
                    ->info('ISO 3166-1 alpha-2 code used when an address has no country, null to keep such addresses without country.')
                    ->defaultValue('DE')
                    ->validate()
                        ->ifTrue(static fn (mixed $v): bool => null !== $v && (!\is_string($v) || 1 !== preg_match('/^[A-Z]{2}$/', $v)))
                        ->thenInvalid('"default_country" must be null or an uppercase ISO 3166-1 alpha-2 code, %s given.')
                    ->end()
                ->end()
                ->integerNode('max_permutations')
                    ->info('Upper bound of address variants returned by the permutator.')
                    ->defaultValue(50)
                    ->min(1)
                ->end()
                ->arrayNode('street_languages')
                    ->info('Languages of the street names per country (ISO 3166-1 alpha-2 => ISO 639-1 codes). Replaces the built-in entry of the country, see StreetLanguageMap::DEFAULT; an empty list disables the language specific permutations for it.')
                    ->example(['BE' => ['fr', 'nl', 'de'], 'IT' => ['it', 'de']])
                    ->useAttributeAsKey('country')
                    ->normalizeKeys(false)
                    ->validate()
                        ->ifTrue(static fn (mixed $v): bool => !\is_array($v) || [] !== array_filter(array_keys($v), static fn (int|string $k): bool => 1 !== preg_match('/^[A-Z]{2}$/', (string) $k)))
                        ->thenInvalid('The keys of "street_languages" must be uppercase ISO 3166-1 alpha-2 codes, %s given.')
                    ->end()
                    ->arrayPrototype()
                        ->scalarPrototype()
                            ->validate()
                                ->ifTrue(static fn (mixed $v): bool => !\is_string($v) || 1 !== preg_match('/^[a-z]{2}$/', $v))
                                ->thenInvalid('A street language must be a lowercase ISO 639-1 code, %s given.')
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();
    }

    /**
     * @param array{default_country: string|null, max_permutations: int, street_languages: array<string, list<string>>} $config
     */
    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $container->parameters()
            ->set('address_kit.default_country', $config['default_country'])
            ->set('address_kit.max_permutations', $config['max_permutations'])
            ->set('address_kit.street_languages', $config['street_languages']);

        $builder->registerForAutoconfiguration(ComponentNormalizerInterface::class)
            ->addTag(self::TAG_NORMALIZER);
        $builder->registerForAutoconfiguration(EncodingNormalizerInterface::class)
            ->addTag(self::TAG_ENCODING_NORMALIZER);
        $builder->registerForAutoconfiguration(WhitespaceNormalizerInterface::class)
            ->addTag(self::TAG_WHITESPACE_NORMALIZER);
        $builder->registerForAutoconfiguration(CountryCodeNormalizerInterface::class)
            ->addTag(self::TAG_COUNTRY_NORMALIZER);
        $builder->registerForAutoconfiguration(CountryZipCodeNormalizerInterface::class)
            ->addTag(self::TAG_ZIP_CODE_NORMALIZER);
        $builder->registerForAutoconfiguration(PermutationStrategyInterface::class)
            ->addTag(self::TAG_PERMUTATION_STRATEGY);

        $container->import('../config/services.php');
    }
}
