<?php

declare(strict_types=1);

namespace Rasuvaeff\PropertyTesting\Tests\Arbitrary;

use Rasuvaeff\PropertyTesting\Arbitrary\BoolArbitrary;
use Rasuvaeff\PropertyTesting\Arbitrary\DictionaryArbitrary;
use Rasuvaeff\PropertyTesting\Arbitrary\IntArbitrary;
use Rasuvaeff\PropertyTesting\Arbitrary\StringArbitrary;
use Rasuvaeff\PropertyTesting\Random;
use Testo\Assert;
use Testo\Assert\ExpectException;
use Testo\Codecov\Covers;
use Testo\Test;

#[Test]
#[Covers(DictionaryArbitrary::class)]
final class DictionaryArbitraryTest
{
    public function generateStaysWithinSizeRange(): void
    {
        // Distinct string keys avoid collisions so the map size tracks the drawn size.
        $arbitrary = new DictionaryArbitrary(new StringArbitrary(5, 5), new IntArbitrary(), 2, 8);
        $random = new Random(1);

        for ($i = 0; $i < 200; ++$i) {
            $count = count($arbitrary->generate($random));

            Assert::true($count >= 1 && $count <= 8);
        }
    }

    public function generateDrawsKeysAndValuesFromTheirArbitraries(): void
    {
        $arbitrary = new DictionaryArbitrary(new StringArbitrary(3, 3), new IntArbitrary(42, 42), 4, 4);
        $dictionary = $arbitrary->generate(new Random(1));

        foreach ($dictionary as $key => $value) {
            Assert::true(is_string($key));
            Assert::same($value, 42);
        }
    }

    public function generateProducesExactlySizeEntriesWithUniqueKeys(): void
    {
        // Wide integer keys make collisions vanishingly unlikely, so a fixed
        // min == max size yields exactly that many entries.
        $arbitrary = new DictionaryArbitrary(new IntArbitrary(1, PHP_INT_MAX), new IntArbitrary(), 5, 5);

        Assert::same(count($arbitrary->generate(new Random(1))), 5);
    }

    public function acceptsMaximumSizeOfOne(): void
    {
        // maxSize === 1 is valid (the boundary of the "at least 1" rule).
        $arbitrary = new DictionaryArbitrary(new IntArbitrary(1, PHP_INT_MAX), new IntArbitrary(7, 7), 1, 1);

        Assert::same(count($arbitrary->generate(new Random(1))), 1);
    }

    public function generatesEmptyMapWhenSizeIsZero(): void
    {
        $arbitrary = new DictionaryArbitrary(new StringArbitrary(1, 5), new IntArbitrary(), 0, 3);
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

    public function shrinkTriesEmptyMapFirstThenHalves(): void
    {
        $candidates = iterator_to_array(
            (new DictionaryArbitrary(new StringArbitrary(1, 5), new IntArbitrary()))
                ->shrink(['a' => 1, 'b' => 2, 'c' => 3, 'd' => 4]),
            false,
        );

        Assert::same($candidates[0], []);
        Assert::same($candidates[1], ['a' => 1, 'b' => 2]);
    }

    public function shrinkReducesValuesAfterSizeKeepingKeys(): void
    {
        $candidates = iterator_to_array(
            (new DictionaryArbitrary(new StringArbitrary(1, 5), new IntArbitrary(0, 10)))
                ->shrink(['a' => 5, 'b' => 5]),
            false,
        );

        Assert::true(in_array(['a' => 0, 'b' => 5], $candidates, true));
        Assert::true(in_array(['a' => 5, 'b' => 0], $candidates, true));
    }

    public function shrinkYieldsTheEmptyMapExactlyOnce(): void
    {
        // The empty map comes only from the minSize===0 guard; the size loop must
        // stop at 1 and never emit a second [].
        $candidates = iterator_to_array(
            (new DictionaryArbitrary(new StringArbitrary(1, 5), new IntArbitrary(0, 10)))
                ->shrink(['a' => 1, 'b' => 2, 'c' => 3, 'd' => 4]),
            false,
        );

        $empties = array_filter($candidates, static fn(array $candidate): bool => $candidate === []);
        Assert::same(count($empties), 1);
    }

    public function shrinkKeepsTheMinimumSizeCandidate(): void
    {
        // With minSize 1 the size-floor slice (one entry) must be produced.
        $candidates = iterator_to_array(
            (new DictionaryArbitrary(new StringArbitrary(1, 5), new IntArbitrary(), 1, 100))
                ->shrink(['a' => 1, 'b' => 2, 'c' => 3, 'd' => 4]),
            false,
        );

        Assert::true(in_array(['a' => 1], $candidates, true));
    }

    public function shrinkNeverEscapesBelowMinimumSize(): void
    {
        $candidates = iterator_to_array(
            (new DictionaryArbitrary(new StringArbitrary(1, 5), new IntArbitrary(), 1, 100))
                ->shrink(['a' => 1, 'b' => 2, 'c' => 3, 'd' => 4]),
            false,
        );

        Assert::false(in_array([], $candidates, true));

        foreach ($candidates as $candidate) {
            Assert::true(count($candidate) >= 1);
        }
    }

    public function shrinkOfEmptyMapYieldsNothing(): void
    {
        Assert::same(
            iterator_to_array((new DictionaryArbitrary(new StringArbitrary(1, 5), new IntArbitrary()))->shrink([])),
            [],
        );
    }

    public function shrinkOfNonArrayYieldsNothing(): void
    {
        Assert::same(
            iterator_to_array((new DictionaryArbitrary(new StringArbitrary(1, 5), new IntArbitrary()))->shrink(42)),
            [],
        );
    }

    public function generateSupportsIntegerKeys(): void
    {
        $arbitrary = new DictionaryArbitrary(new IntArbitrary(1, 1000), new BoolArbitrary(), 3, 3);
        $dictionary = $arbitrary->generate(new Random(1));

        foreach (array_keys($dictionary) as $key) {
            Assert::true(is_int($key));
        }
    }

    #[ExpectException(\InvalidArgumentException::class)]
    public function rejectsKeyArbitraryProducingNonArrayKey(): void
    {
        // A bool key is neither int nor string and cannot index a PHP array.
        (new DictionaryArbitrary(new BoolArbitrary(), new IntArbitrary(), 1, 1))->generate(new Random(1));
    }

    #[ExpectException(\InvalidArgumentException::class)]
    public function rejectsNegativeMinimumSize(): void
    {
        new DictionaryArbitrary(new StringArbitrary(1, 5), new IntArbitrary(), -1, 5);
    }

    #[ExpectException(\InvalidArgumentException::class)]
    public function rejectsZeroMaximumSize(): void
    {
        new DictionaryArbitrary(new StringArbitrary(1, 5), new IntArbitrary(), 0, 0);
    }

    #[ExpectException(\InvalidArgumentException::class)]
    public function rejectsInvertedSize(): void
    {
        new DictionaryArbitrary(new StringArbitrary(1, 5), new IntArbitrary(), 10, 2);
    }
}
