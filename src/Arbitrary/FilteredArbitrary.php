<?php

declare(strict_types=1);

namespace Rasuvaeff\PropertyTesting\Arbitrary;

use Closure;
use Rasuvaeff\PropertyTesting\ArbitraryInterface;
use Rasuvaeff\PropertyTesting\Random;

/**
 * Generates values from a delegate arbitrary, retrying until a predicate holds.
 *
 * Filtering is bounded: after {@see self::MAX_ATTEMPTS} consecutive rejections
 * the generator gives up and returns the last generated value. Use
 * {@see \Rasuvaeff\PropertyTesting\Assume::that()} inside the property when the
 * rejection rate is high, which skips discarded runs cleanly instead of
 * burning random budget here.
 *
 * Shrinking delegates to the inner arbitrary and keeps only candidates that
 * satisfy the predicate.
 *
 * @api
 */
final readonly class FilteredArbitrary implements ArbitraryInterface
{
    private const int MAX_ATTEMPTS = 100;

    /**
     * @param Closure(mixed): bool $predicate
     */
    public function __construct(
        private ArbitraryInterface $inner,
        private Closure $predicate,
    ) {}

    #[\Override]
    public function generate(Random $random): mixed
    {
        $attempt = 0;

        do {
            /** @var mixed $value */
            $value = $this->inner->generate($random);

            if (($this->predicate)($value)) {
                return $value;
            }
            ++$attempt;
        } while ($attempt < self::MAX_ATTEMPTS);

        return $value;
    }

    #[\Override]
    public function shrink(mixed $value): iterable
    {
        /** @var mixed $candidate */
        foreach ($this->inner->shrink($value) as $candidate) {
            if (($this->predicate)($candidate)) {
                yield $candidate;
            }
        }
    }
}
