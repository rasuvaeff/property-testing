<?php

declare(strict_types=1);

namespace Rasuvaeff\PropertyTesting\Arbitrary;

use Rasuvaeff\PropertyTesting\ArbitraryInterface;
use Rasuvaeff\PropertyTesting\Random;

/**
 * Wraps another arbitrary and additionally yields `null` with roughly even odds.
 *
 * Shrinking prefers `null` over delegating to the inner arbitrary.
 *
 * @api
 */
final readonly class NullableArbitrary implements ArbitraryInterface
{
    public function __construct(
        private ArbitraryInterface $inner,
    ) {}

    #[\Override]
    public function generate(Random $random): mixed
    {
        return $random->int(0, 1) === 1
            ? null
            : $this->inner->generate($random);
    }

    #[\Override]
    public function shrink(mixed $value): iterable
    {
        if ($value === null) {
            return;
        }

        yield null;

        /** @var mixed $candidate */
        foreach ($this->inner->shrink($value) as $candidate) {
            yield $candidate;
        }
    }
}
