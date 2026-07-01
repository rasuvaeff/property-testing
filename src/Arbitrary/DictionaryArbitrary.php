<?php

declare(strict_types=1);

namespace Rasuvaeff\PropertyTesting\Arbitrary;

use Rasuvaeff\PropertyTesting\ArbitraryInterface;
use Rasuvaeff\PropertyTesting\Random;

/**
 * Generates associative arrays (maps) whose keys come from a key arbitrary and
 * whose values come from a value arbitrary, then shrinks them by size toward the
 * empty map.
 *
 * Keys must be PHP array keys (int or string); a key arbitrary that produces
 * anything else is a configuration error and throws. Generation draws a size,
 * then that many keys followed by that many values, so seeded runs are
 * reproducible. Because colliding keys overwrite (last value wins), the
 * resulting map may be smaller than the drawn size.
 *
 * @api
 */
final readonly class DictionaryArbitrary implements ArbitraryInterface
{
    public function __construct(
        private ArbitraryInterface $key,
        private ArbitraryInterface $value,
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

    /**
     * @return array<array-key, mixed>
     */
    #[\Override]
    public function generate(Random $random): array
    {
        $size = $random->int($this->minSize, $this->maxSize);

        if ($size === 0) {
            return [];
        }

        $indices = range(1, $size);

        // Draw keys, then values, building each list via array_map so the map is
        // assembled with array_combine instead of per-key mixed assignment.
        // array_combine keeps the last value on a key collision.
        $keys = array_map(
            function (int $i) use ($random): int|string {
                /** @var mixed $key */
                $key = $this->key->generate($random);

                if (!is_int($key) && !is_string($key)) {
                    throw new \InvalidArgumentException(sprintf(
                        'Dictionary key arbitrary must produce int or string keys, got %s',
                        get_debug_type($key),
                    ));
                }

                return $key;
            },
            $indices,
        );

        $values = array_map(
            fn(int $i): mixed => $this->value->generate($random),
            $indices,
        );

        return array_combine($keys, $values);
    }

    #[\Override]
    public function shrink(mixed $value): iterable
    {
        if (!is_array($value) || $value === []) {
            return;
        }

        // 1. Size first: empty map, then progressively smaller halves, preserving
        //    keys so the candidate stays a valid map. Never shrink below minSize,
        //    so the candidate stays within the generated domain.
        if ($this->minSize === 0) {
            yield [];
        }

        $size = count($value);
        while ($size > 1) {
            $size = intdiv($size, 2);

            if ($size >= $this->minSize) {
                yield array_slice($value, 0, $size, true);
            }
        }

        // 2. Then values: shrink one value at a time via the value arbitrary,
        //    keeping the keys fixed. Keys themselves are not shrunk.
        /** @var mixed $element */
        foreach ($value as $key => $element) {
            /** @var mixed $smaller */
            foreach ($this->value->shrink($element) as $smaller) {
                yield array_replace($value, [$key => $smaller]);
            }
        }
    }
}
