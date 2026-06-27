<?php

declare(strict_types=1);

namespace Rasuvaeff\PropertyTesting\Tests\Arbitrary;

use Rasuvaeff\PropertyTesting\Arbitrary\OneOfArbitrary;
use Rasuvaeff\PropertyTesting\Random;
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
            Assert::true(in_array($arbitrary->generate($random), $values, true));
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
            $seen[$arbitrary->generate($random)] = true;
        }

        Assert::same(isset($seen['a'], $seen['b'], $seen['c']), true);
    }

    public function shrinkYieldsAllOtherDistinctValues(): void
    {
        $candidates = iterator_to_array((new OneOfArbitrary('x', 'y', 'z'))->shrink('y'));

        Assert::same($candidates, ['x', 'z']);
    }

    public function shrinkDeduplicatesIdenticalCandidates(): void
    {
        $candidates = iterator_to_array((new OneOfArbitrary(1, 1, 2))->shrink(2));

        Assert::same($candidates, [1]);
    }

    public function shrinkContinuesPastDuplicatesToLaterDistinctValues(): void
    {
        // A duplicate is skipped but the scan must continue: the later distinct
        // value (7) is still yielded, so the dedup uses continue, not break.
        $candidates = iterator_to_array((new OneOfArbitrary(5, 5, 7))->shrink(9));

        Assert::same($candidates, [5, 7]);
    }

    #[ExpectException(\InvalidArgumentException::class)]
    public function rejectsEmptyValueSet(): void
    {
        new OneOfArbitrary();
    }
}
