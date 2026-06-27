<?php

declare(strict_types=1);

namespace Rasuvaeff\PropertyTesting\Tests\Arbitrary;

use Rasuvaeff\PropertyTesting\Arbitrary\BoolArbitrary;
use Rasuvaeff\PropertyTesting\Arbitrary\FrequencyArbitrary;
use Rasuvaeff\PropertyTesting\Arbitrary\IntArbitrary;
use Rasuvaeff\PropertyTesting\Random;
use Testo\Assert;
use Testo\Assert\ExpectException;
use Testo\Codecov\Covers;
use Testo\Test;

#[Test]
#[Covers(FrequencyArbitrary::class)]
final class FrequencyArbitraryTest
{
    public function generatePicksOnlyValuesProducibleByABranch(): void
    {
        $arbitrary = new FrequencyArbitrary([
            [1, new IntArbitrary(0, 0)],
            [1, new IntArbitrary(1, 1)],
        ]);
        $random = new Random(1);

        for ($i = 0; $i < 200; ++$i) {
            Assert::true(in_array($arbitrary->generate($random), [0, 1], true));
        }
    }

    public function generateReachesEveryBranch(): void
    {
        // Equal weights: every branch must be selectable, so all three distinct
        // values appear. Pins the weighted walk against branch-skipping mutants.
        $arbitrary = new FrequencyArbitrary([
            [1, new IntArbitrary(0, 0)],
            [1, new IntArbitrary(1, 1)],
            [1, new IntArbitrary(2, 2)],
        ]);
        $random = new Random(1);
        $seen = [];

        for ($i = 0; $i < 300; ++$i) {
            $seen[$arbitrary->generate($random)] = true;
        }

        Assert::same(isset($seen[0], $seen[1], $seen[2]), true);
    }

    public function lowWeightBranchStaysReachableBesideADominantOne(): void
    {
        // A weight-1 branch next to a weight-50 one must still be reachable: the
        // selection boundary is inclusive, so the last unit of weight is hit.
        $arbitrary = new FrequencyArbitrary([
            [50, new IntArbitrary(7, 7)],
            [1, new IntArbitrary(9, 9)],
        ]);
        $random = new Random(1);
        $seen = [];

        for ($i = 0; $i < 400; ++$i) {
            $seen[$arbitrary->generate($random)] = true;
        }

        Assert::same(isset($seen[7], $seen[9]), true);
    }

    public function singlePairBehavesLikeTheInnerArbitrary(): void
    {
        $arbitrary = new FrequencyArbitrary([[1, new IntArbitrary(3, 3)]]);

        Assert::same($arbitrary->generate(new Random(1)), 3);
    }

    public function shrinkDelegatesToBranchesThatCouldHaveProducedTheValue(): void
    {
        // The value 8 can only come from the int branch; the bool branch ignores
        // it. So candidates are the int shrinks (toward 0), no booleans.
        $candidates = iterator_to_array(
            (new FrequencyArbitrary([
                [1, new IntArbitrary(0, 10)],
                [1, new BoolArbitrary()],
            ]))->shrink(8),
            false,
        );

        Assert::true(in_array(0, $candidates, true));

        foreach ($candidates as $candidate) {
            Assert::true(is_int($candidate));
        }
    }

    public function shrinkUsesTheBranchMatchingTheValueType(): void
    {
        // A boolean value is shrunk only by the bool branch (true -> false).
        $candidates = iterator_to_array(
            (new FrequencyArbitrary([
                [1, new IntArbitrary(0, 10)],
                [1, new BoolArbitrary()],
            ]))->shrink(true),
            false,
        );

        Assert::same($candidates, [false]);
    }

    #[ExpectException(\InvalidArgumentException::class)]
    public function rejectsEmptyPairs(): void
    {
        new FrequencyArbitrary([]);
    }

    #[ExpectException(\InvalidArgumentException::class)]
    public function rejectsZeroWeight(): void
    {
        new FrequencyArbitrary([[0, new IntArbitrary()]]);
    }

    #[ExpectException(\InvalidArgumentException::class)]
    public function rejectsNegativeWeight(): void
    {
        new FrequencyArbitrary([[-1, new IntArbitrary()]]);
    }

    #[ExpectException(\InvalidArgumentException::class)]
    public function rejectsNonArbitraryInPair(): void
    {
        new FrequencyArbitrary([[1, 'not-an-arbitrary']]);
    }

    #[ExpectException(\InvalidArgumentException::class)]
    public function rejectsPairMissingTheArbitrary(): void
    {
        new FrequencyArbitrary([[1]]);
    }

    public function rejectsNonArrayPairWithAStructuralMessage(): void
    {
        // The structural guard must be the one that fires (not a downstream
        // weight/arbitrary check), so the failure points at the pair shape.
        try {
            new FrequencyArbitrary([42]);

            Assert::fail('expected an InvalidArgumentException');
        } catch (\InvalidArgumentException $e) {
            Assert::string($e->getMessage())->contains('pair must be');
        }
    }

    #[ExpectException(\InvalidArgumentException::class)]
    public function rejectsNonIntegerWeight(): void
    {
        new FrequencyArbitrary([[1.5, new IntArbitrary()]]);
    }
}
