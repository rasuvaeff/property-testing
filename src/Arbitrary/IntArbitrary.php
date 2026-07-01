<?php

declare(strict_types=1);

namespace Rasuvaeff\PropertyTesting\Arbitrary;

use Rasuvaeff\PropertyTesting\ArbitraryInterface;
use Rasuvaeff\PropertyTesting\Internal\Boundary;
use Rasuvaeff\PropertyTesting\Random;

/**
 * Generates integers within an inclusive range and shrinks them toward zero.
 *
 * Generation is biased: roughly one draw in {@see BIAS_DENOMINATOR} returns an
 * in-range boundary value (0, ±1, min, max) instead of a uniform one, because
 * bugs cluster at edges. Shrinking is unaffected.
 *
 * @api
 */
final readonly class IntArbitrary implements ArbitraryInterface
{
    private const int BIAS_DENOMINATOR = 5;

    public function __construct(
        private int $min = PHP_INT_MIN,
        private int $max = PHP_INT_MAX,
    ) {
        if ($min > $max) {
            throw new \InvalidArgumentException('Min must be less than or equal to max');
        }
    }

    #[\Override]
    public function generate(Random $random): int
    {
        // Boundary candidates always include min and max, so the list is non-empty.
        if ($random->int(1, self::BIAS_DENOMINATOR) === 1) {
            $boundaries = Boundary::ints($this->min, $this->max);

            return $boundaries[$random->int(0, count($boundaries) - 1)];
        }

        return $random->int($this->min, $this->max);
    }

    #[\Override]
    public function shrink(mixed $value): iterable
    {
        if (!is_int($value) || $value === 0) {
            return;
        }

        // The most aggressive shrink is zero; try it first, then halve the
        // magnitude repeatedly. Candidates are clamped to the configured range
        // so the shrunk counterexample stays within the generated domain.
        $sign = $value <=> 0;
        $magnitude = abs($value);

        yield $this->clamp(0);

        while ($magnitude > 1) {
            $magnitude = intdiv($magnitude, 2);

            yield $this->clamp($sign * $magnitude);
        }
    }

    private function clamp(int $value): int
    {
        return max($this->min, min($this->max, $value));
    }
}
