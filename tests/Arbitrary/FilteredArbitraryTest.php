<?php

declare(strict_types=1);

namespace Rasuvaeff\PropertyTesting\Tests\Arbitrary;

use Rasuvaeff\PropertyTesting\Arbitrary\FilteredArbitrary;
use Rasuvaeff\PropertyTesting\Arbitrary\IntArbitrary;
use Rasuvaeff\PropertyTesting\ArbitraryInterface;
use Rasuvaeff\PropertyTesting\Random;
use Rasuvaeff\PropertyTesting\Shrinkable;
use Rasuvaeff\PropertyTesting\Tests\Support\Trees;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Test;

#[Test]
#[Covers(FilteredArbitrary::class)]
final class FilteredArbitraryTest
{
    public function generateProducesValuesSatisfyingThePredicate(): void
    {
        $arbitrary = new FilteredArbitrary(
            new IntArbitrary(1, 100),
            static fn(int $x): bool => $x % 2 === 0,
        );
        $random = new Random(1);

        for ($i = 0; $i < 100; ++$i) {
            $value = $arbitrary->generate($random)->value;

            Assert::same($value % 2, 0);
        }
    }

    public function shrinkKeepsOnlyCandidatesSatisfyingPredicateAtEveryDepth(): void
    {
        $arbitrary = new FilteredArbitrary(
            new IntArbitrary(0, 100),
            static fn(int $x): bool => $x % 2 === 0,
        );
        $node = Trees::generateWhere($arbitrary, static fn(mixed $v): bool => is_int($v) && $v > 10);

        foreach (Trees::valuesToDepth($node, 3) as $candidate) {
            Assert::same($candidate % 2, 0);
        }
    }

    public function shrinkDelegatesToTheInnerTree(): void
    {
        // The inner shrink tree is actually walked (not short-circuited away):
        // the inner ladder's first candidate is 0, which satisfies "even".
        $arbitrary = new FilteredArbitrary(
            new IntArbitrary(0, 100),
            static fn(int $x): bool => $x % 2 === 0,
        );
        $node = Trees::generateWhere($arbitrary, static fn(mixed $v): bool => is_int($v) && $v > 10);
        $candidates = Trees::childValues($node);

        Assert::true($candidates !== []);
        Assert::same($candidates[0], 0);
    }

    public function rejectedCandidatesArePrunedWithTheirSubtrees(): void
    {
        // Filtering to multiples of 4: every surviving node at any depth is a
        // multiple of 4 — an odd candidate cannot smuggle its subtree through.
        $arbitrary = new FilteredArbitrary(
            new IntArbitrary(0, 100),
            static fn(int $x): bool => $x % 4 === 0,
        );
        $node = Trees::generateWhere($arbitrary, static fn(mixed $v): bool => is_int($v) && $v > 20);

        foreach (Trees::valuesToDepth($node, 3) as $candidate) {
            Assert::same($candidate % 4, 0);
        }
    }

    public function givesUpAfterTheRetryBudgetAndReturnsTheLastValue(): void
    {
        // An unsatisfiable predicate must terminate (bounded retries) and return
        // the last generated value rather than looping forever.
        $arbitrary = new FilteredArbitrary(
            new IntArbitrary(1, 100),
            static fn(int $x): bool => false,
        );

        $value = $arbitrary->generate(new Random(1))->value;

        Assert::true($value >= 1 && $value <= 100);
    }

    public function drawsExactlyTheRetryBudgetBeforeGivingUp(): void
    {
        // With an always-failing predicate the inner arbitrary is sampled exactly
        // MAX_ATTEMPTS (100) times; pins the retry-counter bounds.
        $inner = new class implements ArbitraryInterface {
            public int $calls = 0;

            #[\Override]
            public function generate(Random $random): Shrinkable
            {
                ++$this->calls;

                return Shrinkable::leaf(1);
            }
        };

        (new FilteredArbitrary($inner, static fn(mixed $x): bool => false))->generate(new Random(1));

        Assert::same($inner->calls, 100);
    }
}
