<?php

declare(strict_types=1);

namespace Rasuvaeff\PropertyTesting\Tests\Arbitrary;

use Rasuvaeff\PropertyTesting\Arbitrary\ArrayArbitrary;
use Rasuvaeff\PropertyTesting\Arbitrary\IntArbitrary;
use Rasuvaeff\PropertyTesting\Random;
use Testo\Assert;
use Testo\Assert\ExpectException;
use Testo\Codecov\Covers;
use Testo\Test;

#[Test]
#[Covers(ArrayArbitrary::class)]
final class ArrayArbitraryTest
{
    public function generateStaysWithinSizeRange(): void
    {
        $arbitrary = new ArrayArbitrary(new IntArbitrary(), 2, 8);
        $random = new Random(1);

        for ($i = 0; $i < 200; ++$i) {
            $count = count($arbitrary->generate($random));

            Assert::true($count >= 2 && $count <= 8);
        }
    }

    public function generatesEmptyArrayWhenSizeIsZero(): void
    {
        // range(1, 0) === [1, 0] would make an empty list unreachable; with min size 0
        // an empty array must appear within a reasonable number of draws.
        $arbitrary = new ArrayArbitrary(new IntArbitrary(), 0, 3);
        $random = new Random(1);
        $sawEmpty = false;

        for ($i = 0; $i < 200; ++$i) {
            if ($arbitrary->generate($random) === []) {
                $sawEmpty = true;

                break;
            }
        }

        Assert::true($sawEmpty);
    }

    public function shrinkTriesEmptyArrayFirstThenHalves(): void
    {
        $candidates = iterator_to_array((new ArrayArbitrary(new IntArbitrary()))->shrink([1, 2, 3, 4]), false);

        Assert::same($candidates[0], []);
        Assert::true(in_array([1], $candidates, true));
    }

    public function shrinkReducesElementsAfterLength(): void
    {
        // After the length candidates, each element is shrunk toward zero in place,
        // keeping the array length fixed.
        $candidates = iterator_to_array((new ArrayArbitrary(new IntArbitrary(0, 10)))->shrink([5, 5]), false);

        Assert::true(in_array([0, 5], $candidates, true));
        Assert::true(in_array([5, 0], $candidates, true));
    }

    public function shrinkNeverEscapesBelowMinimumSize(): void
    {
        // A nonEmptyArrayOf-style generator must never shrink to [] (out of domain).
        $candidates = iterator_to_array((new ArrayArbitrary(new IntArbitrary(), 1, 100))->shrink([1, 2, 3, 4]), false);

        Assert::false(in_array([], $candidates, true));

        foreach ($candidates as $candidate) {
            Assert::true(count($candidate) >= 1);
        }
    }

    public function shrinkOfEmptyArrayYieldsNothing(): void
    {
        Assert::same(iterator_to_array((new ArrayArbitrary(new IntArbitrary()))->shrink([])), []);
    }

    public function generateReachesBothExactSizeBounds(): void
    {
        // The configured min and max sizes must both be reachable.
        $arbitrary = new ArrayArbitrary(new IntArbitrary(), 1, 4);
        $random = new Random(1);
        $minSize = PHP_INT_MAX;
        $maxSize = 0;

        for ($i = 0; $i < 200; ++$i) {
            $size = count($arbitrary->generate($random));
            $minSize = min($minSize, $size);
            $maxSize = max($maxSize, $size);
        }

        Assert::same($minSize, 1);
        Assert::same($maxSize, 4);
    }

    public function generateDrawsElementsFromTheElementArbitrary(): void
    {
        // Elements come from the element arbitrary, not from the index range.
        $arbitrary = new ArrayArbitrary(new IntArbitrary(100, 100), 3, 3);

        Assert::same($arbitrary->generate(new Random(1)), [100, 100, 100]);
    }

    public function shrinkYieldsExactLengthThenElementPrefix(): void
    {
        // Length phase ([], halves) immediately followed by the first element
        // candidate; pins that the length loop stops at 1 (no trailing []).
        $candidates = iterator_to_array((new ArrayArbitrary(new IntArbitrary(0, 10)))->shrink([8, 8, 8, 8]), false);

        Assert::same($candidates[0], []);
        Assert::same($candidates[1], [8, 8]);
        Assert::same($candidates[2], [8]);
        Assert::same($candidates[3], [0, 8, 8, 8]);
    }

    public function shrinkElementPhaseShrinksEachPositionInPlace(): void
    {
        // After length candidates, each element is shrunk in place via the element arbitrary.
        $candidates = iterator_to_array((new ArrayArbitrary(new IntArbitrary(0, 10), 4, 4))->shrink([8, 8, 8, 8]), false);

        Assert::true(in_array([0, 8, 8, 8], $candidates, true));
        Assert::true(in_array([8, 8, 8, 0], $candidates, true));
    }

    public function shrinkReindexesNonListInputToAList(): void
    {
        // The element phase re-indexes to a list so positions stay stable even
        // when the failing array has gaps or string keys.
        $candidates = iterator_to_array((new ArrayArbitrary(new IntArbitrary(0, 10), 0, 100))->shrink([2 => 8, 5 => 8]), false);

        Assert::true(in_array([0, 8], $candidates, true));
    }

    public function acceptsMaximumSizeOfOne(): void
    {
        // maxSize === 1 is valid (the boundary of the "at least 1" rule).
        $arbitrary = new ArrayArbitrary(new IntArbitrary(0, 0), 1, 1);

        Assert::same($arbitrary->generate(new Random(1)), [0]);
    }

    public function nonEmptyShrinkKeepsTheMinimumSizeCandidate(): void
    {
        // The length-floor candidate (size === minSize) must be produced.
        $candidates = iterator_to_array((new ArrayArbitrary(new IntArbitrary(7, 7), 1, 100))->shrink([7, 7, 7, 7]), false);

        Assert::true(in_array([7], $candidates, true));
    }

    #[ExpectException(\InvalidArgumentException::class)]
    public function rejectsNegativeMinimumSize(): void
    {
        new ArrayArbitrary(new IntArbitrary(), -1, 5);
    }

    #[ExpectException(\InvalidArgumentException::class)]
    public function rejectsZeroMaximumSize(): void
    {
        new ArrayArbitrary(new IntArbitrary(), 0, 0);
    }

    #[ExpectException(\InvalidArgumentException::class)]
    public function rejectsInvertedSize(): void
    {
        new ArrayArbitrary(new IntArbitrary(), 10, 2);
    }
}
