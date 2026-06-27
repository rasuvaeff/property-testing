<?php

declare(strict_types=1);

namespace Rasuvaeff\PropertyTesting\Tests\Arbitrary;

use Rasuvaeff\PropertyTesting\Arbitrary\BoolArbitrary;
use Rasuvaeff\PropertyTesting\Random;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Test;

#[Test]
#[Covers(BoolArbitrary::class)]
final class BoolArbitraryTest
{
    public function generatesBothTrueAndFalseOverManyRuns(): void
    {
        $arbitrary = new BoolArbitrary();
        $random = new Random(1);

        $true = false;
        $false = false;

        for ($i = 0; $i < 100; ++$i) {
            if ($arbitrary->generate($random)) {
                $true = true;
            } else {
                $false = true;
            }
        }

        Assert::true($true);
        Assert::true($false);
    }

    public function generateIsTrueExactlyWhenUnderlyingDrawIsOne(): void
    {
        // The bool is true iff the underlying [0, 1] draw is 1. Mirror the draw
        // with an identically seeded Random to pin the exact mapping, killing
        // mutations of the draw range and the === comparison.
        $arbitrary = new BoolArbitrary();
        $bools = new Random(7);
        $ints = new Random(7);

        for ($i = 0; $i < 100; ++$i) {
            Assert::same($arbitrary->generate($bools), $ints->int(0, 1) === 1);
        }
    }

    public function shrinkOfTrueYieldsFalse(): void
    {
        Assert::same(iterator_to_array((new BoolArbitrary())->shrink(true)), [false]);
    }

    public function shrinkOfFalseYieldsNothing(): void
    {
        Assert::same(iterator_to_array((new BoolArbitrary())->shrink(false)), []);
    }
}
