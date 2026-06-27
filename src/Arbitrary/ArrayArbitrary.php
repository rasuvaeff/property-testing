<?php

declare(strict_types=1);

namespace Rasuvaeff\PropertyTesting\Arbitrary;

use Rasuvaeff\PropertyTesting\ArbitraryInterface;
use Rasuvaeff\PropertyTesting\Random;

/**
 * Generates lists whose elements come from a delegate arbitrary and shrinks
 * them by length toward the empty array.
 *
 * @api
 */
final readonly class ArrayArbitrary implements ArbitraryInterface
{
    public function __construct(
        private ArbitraryInterface $element,
        private int $minSize = 0,
        private int $maxSize = 100,
    ) {
        if ($minSize < 0) {
            throw new \InvalidArgumentException('Minimum size must be greater than or equal to 0');
        }
        if ($maxSize < 1) {
            throw new \InvalidArgumentException('Maximum size must be greater than or equal to 1');
        }
        if ($minSize > $maxSize) {
            throw new \InvalidArgumentException('Minimum size must be less than or equal to maximum size');
        }
    }

    #[\Override]
    public function generate(Random $random): array
    {
        $size = $random->int($this->minSize, $this->maxSize);

        // Guard size 0 explicitly: range(1, 0) === [1, 0], so a naive range() would
        // yield two elements and an empty list would never be generated.
        return $size === 0 ? [] : array_map(
            fn(int $i): mixed => $this->element->generate($random),
            range(1, $size),
        );
    }

    #[\Override]
    public function shrink(mixed $value): iterable
    {
        if (!is_array($value) || $value === []) {
            return;
        }

        // 1. Length first: empty array, then progressively shorter halves of the
        //    original. Dropping elements is the most aggressive simplification.
        //    Never shrink below minSize, so the candidate stays in the generated
        //    domain (e.g. a nonEmptyArrayOf never shrinks to []).
        if ($this->minSize === 0) {
            yield [];
        }

        $length = count($value);
        while ($length > 1) {
            $length = intdiv($length, 2);

            if ($length >= $this->minSize) {
                yield array_slice($value, 0, $length);
            }
        }

        // 2. Then elements: shrink one element at a time via the element arbitrary,
        //    keeping the array length fixed. Re-indexed to a list so positions are stable.
        $values = array_values($value);

        /** @var mixed $element */
        foreach ($values as $index => $element) {
            /** @var mixed $smaller */
            foreach ($this->element->shrink($element) as $smaller) {
                yield array_replace($values, [$index => $smaller]);
            }
        }
    }
}
