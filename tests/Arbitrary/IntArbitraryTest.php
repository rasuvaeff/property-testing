<?php

declare(strict_types=1);

namespace Rasuvaeff\PropertyTesting\Tests\Arbitrary;

use Rasuvaeff\PropertyTesting\Arbitrary\IntArbitrary;
use Rasuvaeff\PropertyTesting\Random;
use Testo\Assert;
use Testo\Assert\ExpectException;
use Testo\Codecov\Covers;
use Testo\Test;

#[Test]
#[Covers(IntArbitrary::class)]
final class IntArbitraryTest
{
    public function generateStaysWithinInclusiveRange(): void
    {
        $arbitrary = new IntArbitrary(5, 10);
        $random = new Random(1);

        for ($i = 0; $i < 200; ++$i) {
            $value = $arbitrary->generate($random);

            Assert::true($value >= 5 && $value <= 10);
        }
    }

    public function shrinkTriesZeroFirst(): void
    {
        $candidates = iterator_to_array((new IntArbitrary(-1000, 1000))->shrink(100));

        Assert::same($candidates[0], 0);
    }

    public function shrinkCandidatesAreSmallerInMagnitude(): void
    {
        $candidates = iterator_to_array((new IntArbitrary())->shrink(100));

        Assert::true(end($candidates) >= -50 && end($candidates) <= 50);
    }

    public function shrinkOfNegativeValueStaysNegative(): void
    {
        $candidates = iterator_to_array((new IntArbitrary(-1000, 1000))->shrink(-100));

        Assert::same($candidates[0], 0);

        // remaining candidates are negative or zero (toward -100 by halving)
        Assert::true(end($candidates) >= -100 && end($candidates) < 0);
    }

    public function shrinkOfZeroYieldsNothing(): void
    {
        Assert::same(iterator_to_array((new IntArbitrary())->shrink(0)), []);
    }

    public function shrinkCandidatesAreClampedToConfiguredRange(): void
    {
        $candidates = iterator_to_array((new IntArbitrary(50, 100))->shrink(80));

        foreach ($candidates as $candidate) {
            Assert::true($candidate >= 50 && $candidate <= 100);
        }
    }

    public function acceptsAndGeneratesADegenerateRange(): void
    {
        // min === max is a valid (single-value) range and must construct.
        $arbitrary = new IntArbitrary(7, 7);

        Assert::same($arbitrary->generate(new Random(1)), 7);
    }

    public function generateReachesBothExactBounds(): void
    {
        // Pins that the configured min and max are themselves reachable, killing
        // off-by-one mutations of the range bounds in generate().
        $arbitrary = new IntArbitrary(3, 6);
        $random = new Random(1);
        $min = PHP_INT_MAX;
        $max = PHP_INT_MIN;

        for ($i = 0; $i < 200; ++$i) {
            $value = $arbitrary->generate($random);
            $min = min($min, $value);
            $max = max($max, $value);
        }

        Assert::same($min, 3);
        Assert::same($max, 6);
    }

    public function shrinkProducesExactHalvingSequence(): void
    {
        // Zero first, then the magnitude halved repeatedly toward 1.
        $candidates = iterator_to_array((new IntArbitrary())->shrink(8), false);

        Assert::same($candidates, [0, 4, 2, 1]);
    }

    public function generateBiasesTowardBoundaryValues(): void
    {
        // Under pure uniform sampling these edge values would essentially never
        // appear (~3 in a million draws); the bias makes them frequent.
        $arbitrary = new IntArbitrary(0, 1_000_000);
        $random = new Random(1);
        $boundaryHits = 0;

        for ($i = 0; $i < 1000; ++$i) {
            if (in_array($arbitrary->generate($random), [0, 1, 1_000_000], true)) {
                ++$boundaryHits;
            }
        }

        // ~1 draw in 5 is a boundary (~200 of 1000); the band also rules out an
        // inverted condition that would bias ~4 in 5.
        Assert::true($boundaryHits > 100 && $boundaryHits < 400);
    }

    #[ExpectException(\InvalidArgumentException::class)]
    public function rejectsInvertedRange(): void
    {
        new IntArbitrary(10, 5);
    }
}
