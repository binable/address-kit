<?php

declare(strict_types=1);

namespace Binable\AddressKit\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Shared composition logic of the address component constraints:
 * required → NotBlank + rules (sequentially), optional → rules only for non-blank values (sequentially).
 *
 * @internal
 */
trait AddressComponentTrait
{
    /**
     * @param list<Constraint> $rules
     *
     * @return list<Constraint>
     */
    private function sequence(bool $required, array $rules): array
    {
        if ($required) {
            return [new Assert\Sequentially(constraints: [new Assert\NotBlank(normalizer: 'trim'), ...$rules])];
        }

        return [new OptionalSequentially($rules)];
    }
}
