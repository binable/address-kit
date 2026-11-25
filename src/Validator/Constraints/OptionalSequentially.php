<?php

declare(strict_types=1);

namespace Binable\AddressKit\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\Composite;
use Symfony\Component\Validator\Exception\MissingOptionsException;

/**
 * Like Symfony's Sequentially (stops at the first failing constraint), but skips blank values
 * (null, '' or whitespace only). Building block for optional address components, as e.g. Length
 * would otherwise report '' as too short.
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
final class OptionalSequentially extends Composite
{
    /**
     * @var list<Constraint>
     */
    public array $constraints = [];

    /**
     * @param list<Constraint> $constraints
     */
    public function __construct(array $constraints = [], ?array $groups = null, mixed $payload = null)
    {
        if ([] === $constraints) {
            throw new MissingOptionsException(\sprintf('The options "constraints" must be set for constraint "%s".', self::class), ['constraints']);
        }

        $this->constraints = $constraints;

        parent::__construct(null, $groups, $payload);
    }

    protected function getCompositeOption(): string
    {
        return 'constraints';
    }
}
