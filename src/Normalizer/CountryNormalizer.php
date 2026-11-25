<?php

declare(strict_types=1);

namespace Binable\AddressKit\Normalizer;

use Binable\AddressKit\Model\Address;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;
use Symfony\Component\Intl\Countries;
use Symfony\Component\Intl\Exception\MissingResourceException;

/**
 * Converts the country to an upper-case ISO 3166-1 alpha-2 code.
 *
 * Accepts alpha-2 ("de"), alpha-3 ("DEU") and country names in German or English ("Deutschland", "Germany").
 * Runs first, as other normalizers may depend on the country.
 */
#[AsTaggedItem(priority: 100)]
final class CountryNormalizer implements ComponentNormalizerInterface
{
    use WhitespaceTrait;

    /**
     * @var array<string, string>|null lower-cased country name => alpha-2
     */
    private ?array $nameMap = null;

    /**
     * @param list<string> $nameLocales locales used to resolve country names
     */
    public function __construct(
        private readonly ?string $defaultCountry = null,
        private readonly array $nameLocales = ['de', 'en'],
    ) {
    }

    public function normalize(Address $address): Address
    {
        $country = $this->cleanWhitespace($address->country);

        if (null === $country) {
            return $address->withCountry($this->defaultCountry);
        }

        return $address->withCountry($this->toAlpha2($country) ?? $country);
    }

    public function toAlpha2(string $country): ?string
    {
        $upper = mb_strtoupper($country);

        if (2 === \strlen($upper) && Countries::exists($upper)) {
            return $upper;
        }

        if (3 === \strlen($upper) && Countries::alpha3CodeExists($upper)) {
            try {
                return Countries::getAlpha2Code($upper);
            } catch (MissingResourceException) {
                return null;
            }
        }

        return $this->nameMap()[mb_strtolower($country)] ?? null;
    }

    /**
     * @return array<string, string>
     */
    private function nameMap(): array
    {
        if (null === $this->nameMap) {
            $this->nameMap = [];
            foreach ($this->nameLocales as $locale) {
                foreach (Countries::getNames($locale) as $code => $name) {
                    $this->nameMap[mb_strtolower($name)] ??= $code;
                }
            }
        }

        return $this->nameMap;
    }
}
