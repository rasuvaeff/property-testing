<?php

declare(strict_types=1);

namespace Rasuvaeff\PropertyTesting\Tests\Arbitrary;

use Rasuvaeff\PropertyTesting\Arbitrary\FloatArbitrary;
use Rasuvaeff\PropertyTesting\Random;
use Testo\Assert;
use Testo\Assert\ExpectException;
use Testo\Codecov\Covers;
use Testo\Test;

#[Test]
#[Covers(FloatArbitrary::class)]
final class FloatArbitraryTest
{
    public function generateStaysWithinRange(): void
    {
        $arbitrary = new FloatArbitrary(1.5, 3.5);
        $random = new Random(1);

        for ($i = 0; $i < 200; ++$i) {
            $value = $arbitrary->generate($random);

            Assert::true($value >= 1.5 && $value <= 3.5);
        }
    }

    public function shrinkTriesZero(): void
    {
        $candidates = iterator_to_array((new FloatArbitrary())->shrink(2.5));

        Assert::same($candidates, [0.0]);
    }

    public function shrinkTargetsNearestBoundWhenRangeExcludesZero(): void
    {
        // floatBetween(5, 10) must shrink toward the in-range bound (5.0), not 0.0.
        $candidates = iterator_to_array((new FloatArbitrary(5.0, 10.0))->shrink(8.0));

        Assert::same($candidates, [5.0]);
    }

    public function shrinkOfZeroYieldsNothing(): void
    {
        Assert::same(iterator_to_array((new FloatArbitrary())->shrink(0.0)), []);
    }

    public function acceptsAndGeneratesADegenerateRange(): void
    {
        // min === max is a valid single-point range and must construct.
        $arbitrary = new FloatArbitrary(2.0, 2.0);

        Assert::same($arbitrary->generate(new Random(1)), 2.0);
    }

    public function generateBiasesTowardZero(): void
    {
        // Uniform [0, 1) hits exactly 0.0 with probability ~0; the bias makes it
        // frequent.
        $arbitrary = new FloatArbitrary(0.0, 1.0);
        $random = new Random(1);
        $zeroHits = 0;

        for ($i = 0; $i < 1000; ++$i) {
            if ($arbitrary->generate($random) === 0.0) {
                ++$zeroHits;
            }
        }

        // ~1 draw in 5 is the boundary 0.0 (~200 of 1000); the band also rules
        // out an inverted condition that would bias ~4 in 5.
        Assert::true($zeroHits > 100 && $zeroHits < 400);
    }

    public function generateNeverEmitsTheExclusiveUpperBound(): void
    {
        $arbitrary = new FloatArbitrary(0.0, 1.0);
        $random = new Random(1);

        for ($i = 0; $i < 1000; ++$i) {
            Assert::true($arbitrary->generate($random) < 1.0);
        }
    }

    #[ExpectException(\InvalidArgumentException::class)]
    public function rejectsInvertedRange(): void
    {
        new FloatArbitrary(10.0, 1.0);
    }
}
