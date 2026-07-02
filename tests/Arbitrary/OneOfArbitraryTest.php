<?php

declare(strict_types=1);

namespace Rasuvaeff\PropertyTesting\Tests\Arbitrary;

use Rasuvaeff\PropertyTesting\Arbitrary\OneOfArbitrary;
use Rasuvaeff\PropertyTesting\Random;
use Rasuvaeff\PropertyTesting\Tests\Support\Trees;
use Testo\Assert;
use Testo\Assert\ExpectException;
use Testo\Codecov\Covers;
use Testo\Test;

#[Test]
#[Covers(OneOfArbitrary::class)]
final class OneOfArbitraryTest
{
    public function generatePicksOnlyFromGivenValues(): void
    {
        $values = ['a', 'b', 'c'];
        $arbitrary = new OneOfArbitrary(...$values);
        $random = new Random(1);

        for ($i = 0; $i < 200; ++$i) {
            Assert::true(in_array($arbitrary->generate($random)->value, $values, true));
        }
    }

    public function generateCanProduceEveryValueIncludingTheEndpoints(): void
    {
        // Every index in [0, count - 1] must be reachable, so the first and last
        // values appear; this pins the index range against off-by-one mutations.
        $arbitrary = new OneOfArbitrary('a', 'b', 'c');
        $random = new Random(1);
        $seen = [];

        for ($i = 0; $i < 200; ++$i) {
            $seen[$arbitrary->generate($random)->value] = true;
        }

        Assert::same(isset($seen['a'], $seen['b'], $seen['c']), true);
    }

    public function shrinkYieldsOnlyEarlierValues(): void
    {
        // Earlier values are "smaller": the middle value shrinks to the first
        // one only, never sideways to the last — this is what guarantees the
        // shrink index strictly decreases and the loop terminates.
        $node = Trees::generateWhere(new OneOfArbitrary('x', 'y', 'z'), static fn(mixed $v): bool => $v === 'y');

        Assert::same(Trees::childValues($node), ['x']);
    }

    public function lastValueShrinksThroughAllEarlierDistinctValues(): void
    {
        $node = Trees::generateWhere(new OneOfArbitrary('x', 'y', 'z'), static fn(mixed $v): bool => $v === 'z');

        Assert::same(Trees::childValues($node), ['x', 'y']);
    }

    public function firstValueDoesNotShrink(): void
    {
        $node = Trees::generateWhere(new OneOfArbitrary('x', 'y', 'z'), static fn(mixed $v): bool => $v === 'x');

        Assert::same(Trees::childValues($node), []);
    }

    public function candidatesCarryTheirOwnEarlierOnlySubtrees(): void
    {
        // Descending into 'y' (from 'z') offers 'x'; descending into 'x' ends.
        $node = Trees::generateWhere(new OneOfArbitrary('x', 'y', 'z'), static fn(mixed $v): bool => $v === 'z');

        $children = [];
        foreach ($node->shrinks() as $child) {
            $children[] = $child;
        }

        Assert::same($children[1]->value, 'y');
        Assert::same(Trees::childValues($children[1]), ['x']);
        Assert::same(Trees::childValues($children[0]), []);
    }

    public function shrinkDeduplicatesIdenticalCandidates(): void
    {
        $node = Trees::generateWhere(new OneOfArbitrary(1, 1, 2), static fn(mixed $v): bool => $v === 2);

        Assert::same(Trees::childValues($node), [1]);
    }

    public function shrinkSkipsEarlierValuesEqualToTheCurrentOne(): void
    {
        // The value 5 at index 1 must not offer the identical 5 at index 0.
        $arbitrary = new OneOfArbitrary(5, 5, 7);
        $random = new Random(1);

        for ($i = 0; $i < 100; ++$i) {
            $node = $arbitrary->generate($random);

            if ($node->value === 5) {
                Assert::same(Trees::childValues($node), []);
            } else {
                Assert::same(Trees::childValues($node), [5]);
            }
        }
    }

    #[ExpectException(\InvalidArgumentException::class)]
    public function rejectsEmptyValueSet(): void
    {
        new OneOfArbitrary();
    }
}
