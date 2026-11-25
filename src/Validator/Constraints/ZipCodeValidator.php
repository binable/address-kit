<?php

declare(strict_types=1);

namespace Binable\AddressKit\Validator\Constraints;

use Symfony\Component\PropertyAccess\Exception\ExceptionInterface as PropertyAccessException;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\ConstraintDefinitionException;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;
use ZipCodeValidator\Constraints\ZipCode as CountryZipCode;

/**
 * Resolves the country and delegates the country-specific check to barbieswimcrew/zip-code-validator.
 */
final class ZipCodeValidator extends ConstraintValidator
{
    private readonly PropertyAccessorInterface $propertyAccessor;

    public function __construct(?PropertyAccessorInterface $propertyAccessor = null)
    {
        $this->propertyAccessor = $propertyAccessor ?? PropertyAccess::createPropertyAccessor();
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof ZipCode) {
            throw new UnexpectedTypeException($constraint, ZipCode::class);
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
            if (1 !== preg_match(ZipCode::GENERIC_PATTERN, $value)) {
                $this->context->buildViolation($constraint->message)
                    ->setParameter('{{ value }}', $this->formatValue($value))
                    ->setCode(ZipCode::INVALID_FORMAT_ERROR)
                    ->addViolation();
            }

            return;
        }

        // Run the country specific check in a separate validation run, so the violation is
        // reported with this constraint's message, parameters and error code.
        $violations = $this->context->getValidator()->validate($value, new CountryZipCode(
            iso: $country,
            strict: $constraint->strict,
            caseSensitiveCheck: $constraint->caseSensitive,
        ));

        if (\count($violations) > 0) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ value }}', $this->formatValue($value))
                ->setParameter('{{ country }}', $country)
                ->setCode(ZipCode::INVALID_FORMAT_ERROR)
                ->addViolation();
        }
    }

    private function resolveCountry(ZipCode $constraint): ?string
    {
        if (null !== $constraint->country) {
            return strtoupper($constraint->country);
        }

        if (null === $constraint->countryPath) {
            return null;
        }

        $object = $this->context->getObject();
        if (null === $object) {
            return null;
        }

        try {
            $country = $this->propertyAccessor->getValue($object, $constraint->countryPath);
        } catch (PropertyAccessException $e) {
            throw new ConstraintDefinitionException(\sprintf('Cannot read country from path "%s": %s', $constraint->countryPath, $e->getMessage()), 0, $e);
        }

        if (!\is_string($country) || '' === trim($country)) {
            return null;
        }

        return strtoupper(trim($country));
    }
}
