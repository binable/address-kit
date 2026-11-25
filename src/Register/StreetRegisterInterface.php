<?php

declare(strict_types=1);

namespace Binable\AddressKit\Register;

use Binable\AddressKit\Model\Address;

/**
 * Source of the known street names, e.g. a database table, an import of the official street
 * register or an API. Used by the {@see \Binable\AddressKit\Validator\Constraints\KnownStreet}
 * constraint.
 *
 * Register your implementation as the service for this interface:
 *
 *     services:
 *         Binable\AddressKit\Register\StreetRegisterInterface: '@App\Address\MyStreetRegister'
 */
interface StreetRegisterInterface
{
    /**
     * The street names known for the ZIP code / city / country of the given address. The street
     * of the address is the one being checked and must not be used for the lookup.
     *
     * @return iterable<string> empty when the area is unknown - the street is not checked then
     */
    public function getStreets(Address $address): iterable;
}
