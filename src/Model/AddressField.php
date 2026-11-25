<?php

declare(strict_types=1);

namespace Binable\AddressKit\Model;

/**
 * The components of an {@see Address}; the value is the property name.
 */
enum AddressField: string
{
    case Street = 'street';
    case HouseNumber = 'houseNumber';
    case ZipCode = 'zipCode';
    case City = 'city';
    case Country = 'country';
}
