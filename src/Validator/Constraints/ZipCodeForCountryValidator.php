<?php

declare(strict_types=1);

namespace Binable\AddressKit\Validator\Constraints;

use Symfony\Component\PropertyAccess\Exception\ExceptionInterface as PropertyAccessException;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;
use ZipCodeValidator\Constraints\ZipCode as BarbieswimcrewZipCode;

/**
 * Resolves the country and delegates the format check to barbieswimcrew/zip-code-validator.
 */
final class ZipCodeForCountryValidator extends ConstraintValidator
{
    private readonly PropertyAccessorInterface $propertyAccessor;

    public function __construct(?PropertyAccessorInterface $propertyAccessor = null)
    {
        $this->propertyAccessor = $propertyAccessor ?? PropertyAccess::createPropertyAccessor();
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof ZipCodeForCountry) {
            throw new UnexpectedTypeException($constraint, ZipCodeForCountry::class);
        }

        if (null === $value || '' === $value) {
            return;
        }

        if (!\is_string($value) && !\is_int($value)) {
            throw new UnexpectedValueException($value, 'string|int');
        }

        $value = trim((string) $value);
        $country = $this->resolveCountry($constraint);

        if (null === $country) {
            return;
        }

        // Separate validation run, so the violation is reported with this constraint's message, parameters and code.
        $violations = $this->context->getValidator()->validate($value, new BarbieswimcrewZipCode(
            iso: $country,
            strict: $constraint->strict,
            caseSensitiveCheck: $constraint->caseSensitive,
        ));

        if (\count($violations) > 0) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ value }}', $this->formatValue($value))
                ->setParameter('{{ country }}', $country)
                ->setCode(ZipCodeForCountry::INVALID_FORMAT_ERROR)
                ->addViolation();
        }
    }

    private function resolveCountry(ZipCodeForCountry $constraint): ?string
    {
        if (null !== $constraint->country) {
            return strtoupper($constraint->country);
        }

        $object = $this->context->getObject();
        if (null === $object || null === $constraint->countryPath) {
            return null;
        }

        try {
            if (!$this->propertyAccessor->isReadable($object, $constraint->countryPath)) {
                return null;
            }

            $country = $this->propertyAccessor->getValue($object, $constraint->countryPath);
        } catch (PropertyAccessException) {
            return null;
        }

        if (!\is_string($country) || '' === trim($country)) {
            return null;
        }

        return strtoupper(trim($country));
    }
}
