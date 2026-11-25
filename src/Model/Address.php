<?php

declare(strict_types=1);

namespace Binable\AddressKit\Model;

use Binable\AddressKit\Validator\Constraints as AddressAssert;

/**
 * Immutable postal address value object.
 *
 * The country is stored as ISO 3166-1 alpha-2 code (e.g. "DE").
 * Street, ZIP code, city and country are required; the house number is optional.
 */
final readonly class Address
{
    public function __construct(
        #[AddressAssert\Street]
        public ?string $street = null,
        #[AddressAssert\HouseNumber]
        public ?string $houseNumber = null,
        #[AddressAssert\ZipCode]
        public ?string $zipCode = null,
        #[AddressAssert\City]
        public ?string $city = null,
        #[AddressAssert\Country]
        public ?string $country = null,
    ) {
    }

    public function withStreet(?string $street): self
    {
        return new self($street, $this->houseNumber, $this->zipCode, $this->city, $this->country);
    }

    public function withHouseNumber(?string $houseNumber): self
    {
        return new self($this->street, $houseNumber, $this->zipCode, $this->city, $this->country);
    }

    public function withZipCode(?string $zipCode): self
    {
        return new self($this->street, $this->houseNumber, $zipCode, $this->city, $this->country);
    }

    public function withCity(?string $city): self
    {
        return new self($this->street, $this->houseNumber, $this->zipCode, $city, $this->country);
    }

    public function withCountry(?string $country): self
    {
        return new self($this->street, $this->houseNumber, $this->zipCode, $this->city, $country);
    }

    public function get(AddressField $field): ?string
    {
        return match ($field) {
            AddressField::Street => $this->street,
            AddressField::HouseNumber => $this->houseNumber,
            AddressField::ZipCode => $this->zipCode,
            AddressField::City => $this->city,
            AddressField::Country => $this->country,
        };
    }

    public function with(AddressField $field, ?string $value): self
    {
        return match ($field) {
            AddressField::Street => $this->withStreet($value),
            AddressField::HouseNumber => $this->withHouseNumber($value),
            AddressField::ZipCode => $this->withZipCode($value),
            AddressField::City => $this->withCity($value),
            AddressField::Country => $this->withCountry($value),
        };
    }

    public function equals(self $other): bool
    {
        return $this->street === $other->street
            && $this->houseNumber === $other->houseNumber
            && $this->zipCode === $other->zipCode
            && $this->city === $other->city
            && $this->country === $other->country;
    }
}
