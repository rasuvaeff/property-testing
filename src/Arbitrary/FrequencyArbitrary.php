<?php

declare(strict_types=1);

namespace Rasuvaeff\PropertyTesting\Arbitrary;

use Rasuvaeff\PropertyTesting\ArbitraryInterface;
use Rasuvaeff\PropertyTesting\Random;

/**
 * Weighted choice among several arbitraries: each `[weight, arbitrary]` pair is
 * picked with probability proportional to its (positive integer) weight, then
 * the chosen arbitrary produces the value.
 *
 * The produced value carries no record of which branch made it, so shrinking
 * delegates to every inner arbitrary: each built-in arbitrary's `shrink()` is
 * type-discriminating and ignores values it could not have produced, so only
 * the branches that could have generated the value contribute candidates.
 *
 * @api
 */
final readonly class FrequencyArbitrary implements ArbitraryInterface
{
    /** @var non-empty-list<array{0: int, 1: ArbitraryInterface}> */
    private array $pairs;

    private int $totalWeight;

    /**
     * @param iterable<mixed> $pairs Each item must be `[int $weight, ArbitraryInterface $arbitrary]` with $weight >= 1.
     */
    public function __construct(iterable $pairs)
    {
        $normalized = [];
        $total = 0;

        foreach ($pairs as $pair) {
            if (!is_array($pair)) {
                throw new \InvalidArgumentException('Frequency pair must be [int $weight, ArbitraryInterface $arbitrary]');
            }

            $weight = $pair[0] ?? null;
            $arbitrary = $pair[1] ?? null;

            if (!is_int($weight) || $weight < 1) {
                throw new \InvalidArgumentException('Frequency weight must be an integer greater than or equal to 1');
            }
            if (!$arbitrary instanceof ArbitraryInterface) {
                throw new \InvalidArgumentException('Frequency pair must contain an ArbitraryInterface');
            }

            $total += $weight;
            $normalized[] = [$weight, $arbitrary];
        }

        if ($normalized === []) {
            throw new \InvalidArgumentException('Frequency requires at least one weighted arbitrary');
        }

        $this->pairs = $normalized;
        $this->totalWeight = $total;
    }

    #[\Override]
    public function generate(Random $random): mixed
    {
        $target = $random->int(1, $this->totalWeight);

        foreach ($this->pairs as [$weight, $arbitrary]) {
            $target -= $weight;

            if ($target <= 0) {
                return $arbitrary->generate($random);
            }
        }

        // $target starts in [1, totalWeight] and the weights sum to totalWeight,
        // so the cumulative subtraction always crosses zero on some branch; the
        // loop therefore always returns. This throw only satisfies the compiler.
        throw new \LogicException('Frequency selection failed to pick a branch');
    }

    #[\Override]
    public function shrink(mixed $value): iterable
    {
        foreach ($this->pairs as [, $arbitrary]) {
            /** @var mixed $candidate */
            foreach ($arbitrary->shrink($value) as $candidate) {
                yield $candidate;
            }
        }
    }
}
