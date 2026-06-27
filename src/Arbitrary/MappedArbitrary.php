<?php

declare(strict_types=1);

namespace Rasuvaeff\PropertyTesting\Arbitrary;

use Closure;
use Rasuvaeff\PropertyTesting\ArbitraryInterface;
use Rasuvaeff\PropertyTesting\Random;

/**
 * Transforms each value produced by a delegate arbitrary through a pure function.
 *
 * Shrinking delegates to the inner arbitrary and re-applies the mapping so that
 * the shrunk counterexample is reported in the transformed domain.
 *
 * @api
 */
final readonly class MappedArbitrary implements ArbitraryInterface
{
    /**
     * @param Closure(mixed): mixed $map
     */
    public function __construct(
        private ArbitraryInterface $inner,
        private Closure $map,
    ) {}

    #[\Override]
    public function generate(Random $random): mixed
    {
        return ($this->map)($this->inner->generate($random));
    }

    #[\Override]
    public function shrink(mixed $value): iterable
    {
        // The mapped domain cannot be inverted in general (the mapping may not
        // be injective), so no shrinking is attempted on the transformed value.
        // Properties that rely on shrinking should map within an arbitrary that
        // itself shrinks, or keep the mapping reversible.
        yield from [];
    }
}
