<?php

declare(strict_types=1);

namespace Binable\AddressKit\Validator\Constraints;

use Binable\AddressKit\Matching\StreetTypo;
use Binable\AddressKit\Model\Address;
use Binable\AddressKit\Permutation\AddressPermutator;
use Binable\AddressKit\Register\StreetRegisterInterface;
use Symfony\Component\PropertyAccess\Exception\ExceptionInterface as PropertyAccessException;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\LogicException;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

final class KnownStreetValidator extends ConstraintValidator
{
    private readonly AddressPermutator $permutator;
    private readonly PropertyAccessorInterface $propertyAccessor;

    public function __construct(
        private readonly ?StreetRegisterInterface $register = null,
        ?AddressPermutator $permutator = null,
        ?PropertyAccessorInterface $propertyAccessor = null,
        private readonly ?string $defaultCountry = null,
    ) {
        $this->permutator = $permutator ?? AddressPermutator::createDefault();
        $this->propertyAccessor = $propertyAccessor ?? PropertyAccess::createPropertyAccessor();
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof KnownStreet) {
            throw new UnexpectedTypeException($constraint, KnownStreet::class);
        }

        if (null === $value || '' === $value) {
            return;
        }

        if (!\is_string($value)) {
            throw new UnexpectedValueException($value, 'string');
        }

        $address = $this->buildAddress(trim($value), $constraint);
        $known = $this->getKnownStreets($address, $constraint);
        if ([] === $known) {
            return;
        }

        $tried = [];
        foreach ($this->permutator->permute($address) as $variant) {
            if (null !== $variant->street) {
                $tried[$variant->street] = true;
            }
        }
        $tried = array_map(strval(...), array_keys($tried));

        $lookup = array_flip(array_map(static fn (string $street): string => mb_strtolower(trim($street)), $known));
        foreach ($tried as $street) {
            if (isset($lookup[mb_strtolower($street)])) {
                return;
            }
        }

        $suggestion = StreetTypo::findUnique($tried, $known);
        if (null !== $suggestion && $constraint->allowTypos) {
            return;
        }

        if (null !== $suggestion) {
            $this->context->buildViolation($constraint->typoMessage)
                ->setParameter('{{ value }}', $this->formatValue($value))
                ->setParameter('{{ suggestion }}', $this->formatValue($suggestion))
                ->setCode(KnownStreet::TYPO_ERROR)
                ->addViolation();

            return;
        }

        $this->context->buildViolation($constraint->message)
            ->setParameter('{{ value }}', $this->formatValue($value))
            ->setCode(KnownStreet::UNKNOWN_STREET_ERROR)
            ->addViolation();
    }

    /**
     * @return list<string>
     */
    private function getKnownStreets(Address $address, KnownStreet $constraint): array
    {
        if (null !== $constraint->streets) {
            $streets = $constraint->streets;
        } elseif (null !== $this->register) {
            $streets = $this->register->getStreets($address);
        } else {
            throw new LogicException(\sprintf('The constraint "%s" needs either the option "streets" or a service implementing "%s".', KnownStreet::class, StreetRegisterInterface::class));
        }

        $known = [];
        foreach ($streets as $street) {
            if ('' !== trim($street)) {
                $known[] = $street;
            }
        }

        return $known;
    }

    private function buildAddress(string $street, KnownStreet $constraint): Address
    {
        $object = $this->context->getObject();

        $address = $object instanceof Address
            ? $object->withStreet($street)
            : new Address(
                $street,
                null,
                $this->read($object, $constraint->zipCodePath),
                $this->read($object, $constraint->cityPath),
                $this->read($object, $constraint->countryPath),
            );

        $country = $constraint->country ?? $address->country ?? $this->defaultCountry;

        return $address->withCountry(null !== $country && '' !== trim($country) ? strtoupper(trim($country)) : null);
    }

    private function read(?object $object, ?string $path): ?string
    {
        if (null === $object || null === $path) {
            return null;
        }

        try {
            if (!$this->propertyAccessor->isReadable($object, $path)) {
                return null;
            }

            $value = $this->propertyAccessor->getValue($object, $path);
        } catch (PropertyAccessException) {
            return null;
        }

        if (!\is_string($value) && !\is_int($value)) {
            return null;
        }

        $value = trim((string) $value);

        return '' === $value ? null : $value;
    }
}
