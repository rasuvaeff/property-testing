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

    #[ExpectException(\InvalidArgumentException::class)]
    public function rejectsInvertedRange(): void
    {
        new FloatArbitrary(10.0, 1.0);
    }
}
