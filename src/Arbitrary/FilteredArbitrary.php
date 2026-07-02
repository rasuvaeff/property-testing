<?php

declare(strict_types=1);

namespace Rasuvaeff\PropertyTesting\Arbitrary;

use Closure;
use Rasuvaeff\PropertyTesting\ArbitraryInterface;
use Rasuvaeff\PropertyTesting\Random;
use Rasuvaeff\PropertyTesting\Shrinkable;

/**
 * Generates values from a delegate arbitrary, retrying until a predicate holds.
 *
 * Filtering is bounded: after {@see self::MAX_ATTEMPTS} consecutive rejections
 * the generator gives up and returns the last generated value. Use
 * {@see \Rasuvaeff\PropertyTesting\Assume::that()} inside the property when the
 * rejection rate is high, which skips discarded runs cleanly instead of
 * burning random budget here.
 *
 * Shrinking walks the inner value's tree, keeping only branches whose value
 * satisfies the predicate (a rejected candidate's subtree is pruned with it).
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
    public function generate(Random $random): Shrinkable
    {
        $attempt = 0;

        do {
            $shrinkable = $this->inner->generate($random);

            if (($this->predicate)($shrinkable->value)) {
                return $this->filtered($shrinkable);
            }
            ++$attempt;
        } while ($attempt < self::MAX_ATTEMPTS);

        return $this->filtered($shrinkable);
    }

    private function filtered(Shrinkable $shrinkable): Shrinkable
    {
        return Shrinkable::of($shrinkable->value, function () use ($shrinkable): \Generator {
            foreach ($shrinkable->shrinks() as $candidate) {
                if (($this->predicate)($candidate->value)) {
                    yield $this->filtered($candidate);
                }
            }
        });
    }
}
