<?php

declare(strict_types=1);

namespace Rasuvaeff\PropertyTesting\Tests\Arbitrary;

use Rasuvaeff\PropertyTesting\Arbitrary\BoolArbitrary;
use Rasuvaeff\PropertyTesting\Arbitrary\DictionaryArbitrary;
use Rasuvaeff\PropertyTesting\Arbitrary\IntArbitrary;
use Rasuvaeff\PropertyTesting\Arbitrary\StringArbitrary;
use Rasuvaeff\PropertyTesting\Random;
use Rasuvaeff\PropertyTesting\Tests\Support\Trees;
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
            $count = count($arbitrary->generate($random)->value);

            Assert::true($count >= 1 && $count <= 8);
        }
    }

    public function generateDrawsKeysAndValuesFromTheirArbitraries(): void
    {
        $arbitrary = new DictionaryArbitrary(new StringArbitrary(3, 3), new IntArbitrary(42, 42), 4, 4);
        $dictionary = $arbitrary->generate(new Random(1))->value;

        foreach ($dictionary as $key => $value) {
            Assert::true(is_string($key));
            Assert::same($value, 42);
        }
    }

    public function generateProducesExactlySizeEntriesWithUniqueKeys(): void
    {
        // Long random string keys make collisions vanishingly unlikely, so a
        // fixed min == max size yields exactly that many entries. (String keys
        // avoid IntArbitrary's boundary bias, which would inflate collisions.)
        $arbitrary = new DictionaryArbitrary(new StringArbitrary(20, 20), new IntArbitrary(), 5, 5);

        Assert::same(count($arbitrary->generate(new Random(1))->value), 5);
    }

    public function acceptsMaximumSizeOfOne(): void
    {
        // maxSize === 1 is valid (the boundary of the "at least 1" rule).
        $arbitrary = new DictionaryArbitrary(new StringArbitrary(20, 20), new IntArbitrary(7, 7), 1, 1);

        Assert::same(count($arbitrary->generate(new Random(1))->value), 1);
    }

    public function generatesEmptyMapWhenSizeIsZero(): void
    {
        $arbitrary = new DictionaryArbitrary(new StringArbitrary(1, 5), new IntArbitrary(), 0, 3);
        $random = new Random(1);
        $sawEmpty = false;

        for ($i = 0; $i < 200; ++$i) {
            if ($arbitrary->generate($random)->value === []) {
                $sawEmpty = true;

                break;
            }
        }

        Assert::true($sawEmpty);
    }

    public function shrinkTriesEmptyMapFirstThenHalvesPreservingKeys(): void
    {
        $node = Trees::generateWhere(
            new DictionaryArbitrary(new StringArbitrary(5, 5), new IntArbitrary(0, 10), 0, 8),
            static fn(mixed $v): bool => is_array($v) && count($v) === 4,
        );
        $value = $node->value;
        $candidates = Trees::childValues($node);

        Assert::same($candidates[0], []);
        Assert::same($candidates[1], array_slice($value, 0, 2, true));
        Assert::same($candidates[2], array_slice($value, 0, 1, true));
    }

    public function shrinkYieldsTheEmptyMapExactlyOnce(): void
    {
        // The empty map comes only from the minSize===0 guard; the size loop must
        // stop at 1 and never emit a second [].
        $node = Trees::generateWhere(
            new DictionaryArbitrary(new StringArbitrary(5, 5), new IntArbitrary(0, 10), 0, 8),
            static fn(mixed $v): bool => is_array($v) && count($v) === 4,
        );

        $empties = array_filter(Trees::childValues($node), static fn(mixed $candidate): bool => $candidate === []);
        Assert::same(count($empties), 1);
    }

    public function shrinkReducesValuesInPlaceKeepingKeys(): void
    {
        // A fixed size blocks the size phase: the single entry's value shrinks
        // through its own tree while the key stays fixed.
        $node = Trees::generateWhere(
            new DictionaryArbitrary(new StringArbitrary(5, 5), new IntArbitrary(0, 10), 1, 1),
            static fn(mixed $v): bool => is_array($v) && count($v) === 1 && reset($v) === 8,
        );
        $key = array_key_first($node->value);

        Assert::same(Trees::childValues($node), [
            [$key => 0], [$key => 4], [$key => 6], [$key => 7],
        ]);
    }

    public function shrinkKeepsTheMinimumSizeCandidate(): void
    {
        // With minSize 1 the size-floor slice (one entry) must be produced.
        $node = Trees::generateWhere(
            new DictionaryArbitrary(new StringArbitrary(5, 5), new IntArbitrary(), 1, 8),
            static fn(mixed $v): bool => is_array($v) && count($v) === 4,
        );
        $value = $node->value;

        Assert::true(in_array(array_slice($value, 0, 1, true), Trees::childValues($node), true));
    }

    public function shrinkNeverEscapesBelowMinimumSize(): void
    {
        $node = Trees::generateWhere(
            new DictionaryArbitrary(new StringArbitrary(5, 5), new IntArbitrary(0, 5), 1, 8),
            static fn(mixed $v): bool => is_array($v) && count($v) === 4,
        );

        foreach (Trees::valuesToDepth($node, 2) as $candidate) {
            Assert::true(count($candidate) >= 1);
        }
    }

    public function shrinkOfEmptyMapYieldsNothing(): void
    {
        $node = Trees::generateWhere(
            new DictionaryArbitrary(new StringArbitrary(1, 5), new IntArbitrary(), 0, 3),
            static fn(mixed $v): bool => $v === [],
        );

        Assert::same(Trees::childValues($node), []);
    }

    public function generateSupportsIntegerKeys(): void
    {
        $arbitrary = new DictionaryArbitrary(new IntArbitrary(1, 1000), new BoolArbitrary(), 3, 3);
        $dictionary = $arbitrary->generate(new Random(1))->value;

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
