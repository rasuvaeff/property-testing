<?php

declare(strict_types=1);

namespace Rasuvaeff\PropertyTesting\Arbitrary;

use Rasuvaeff\PropertyTesting\ArbitraryInterface;
use Rasuvaeff\PropertyTesting\Random;

/**
 * Generates floats within an inclusive range.
 *
 * Shrinking floats reliably is hard (no natural "smallest" value), so this
 * arbitrary shrinks to a single candidate: zero, clamped into the configured
 * range. For properties that need fine-grained shrinking on a numeric input,
 * drive the property with an integer generator and convert to a float inside
 * the test body — the integer then shrinks normally. Note that
 * {@see \Rasuvaeff\PropertyTesting\Gen::map()} does NOT shrink its result, so
 * mapping an integer to a float discards shrinking.
 *
 * @api
 */
final readonly class FloatArbitrary implements ArbitraryInterface
{
    public function __construct(
        private float $min = 0.0,
        private float $max = 1.0,
    ) {
        if ($min > $max) {
            throw new \InvalidArgumentException('Min must be less than or equal to max');
        }
    }

    #[\Override]
    public function generate(Random $random): float
    {
        return $this->min + $random->float() * ($this->max - $this->min);
    }

    #[\Override]
    public function shrink(mixed $value): iterable
    {
        if (!is_float($value)) {
            return;
        }

        // Shrink toward zero, clamped to the configured range so the candidate
        // stays in the generated domain (mirrors IntArbitrary). For a range that
        // excludes zero, e.g. [5.0, 10.0], the target is the nearest bound (5.0).
        $target = max($this->min, min($this->max, 0.0));

        if ($value !== $target) {
            yield $target;
        }
    }
}
