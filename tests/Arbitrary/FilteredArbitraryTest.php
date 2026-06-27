<?php

declare(strict_types=1);

namespace Rasuvaeff\PropertyTesting\Tests\Arbitrary;

use Rasuvaeff\PropertyTesting\Arbitrary\FilteredArbitrary;
use Rasuvaeff\PropertyTesting\Arbitrary\IntArbitrary;
use Rasuvaeff\PropertyTesting\ArbitraryInterface;
use Rasuvaeff\PropertyTesting\Random;
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
            $value = $arbitrary->generate($random);

            Assert::same($value % 2, 0);
        }
    }

    public function shrinkKeepsOnlyCandidatesSatisfyingPredicate(): void
    {
        $arbitrary = new FilteredArbitrary(
            new IntArbitrary(0, 100),
            static fn(int $x): bool => $x % 2 === 0,
        );

        foreach ($arbitrary->shrink(50) as $candidate) {
            Assert::same($candidate % 2, 0);
        }
    }

    public function shrinkDelegatesToTheInnerArbitrary(): void
    {
        // The inner shrink is actually iterated (not short-circuited away), so a
        // predicate-satisfying input yields at least one candidate.
        $arbitrary = new FilteredArbitrary(
            new IntArbitrary(0, 100),
            static fn(int $x): bool => $x % 2 === 0,
        );

        $candidates = iterator_to_array($arbitrary->shrink(64), false);

        Assert::true($candidates !== []);
        Assert::same($candidates[0], 0);
    }

    public function givesUpAfterTheRetryBudgetAndReturnsTheLastValue(): void
    {
        // An unsatisfiable predicate must terminate (bounded retries) and return
        // the last generated value rather than looping forever.
        $arbitrary = new FilteredArbitrary(
            new IntArbitrary(1, 100),
            static fn(int $x): bool => false,
        );

        $value = $arbitrary->generate(new Random(1));

        Assert::true($value >= 1 && $value <= 100);
    }

    public function drawsExactlyTheRetryBudgetBeforeGivingUp(): void
    {
        // With an always-failing predicate the inner arbitrary is sampled exactly
        // MAX_ATTEMPTS (100) times; pins the retry-counter bounds.
        $inner = new class implements ArbitraryInterface {
            public int $calls = 0;

            #[\Override]
            public function generate(Random $random): mixed
            {
                ++$this->calls;

                return 1;
            }

            #[\Override]
            public function shrink(mixed $value): iterable
            {
                return [];
            }
        };

        (new FilteredArbitrary($inner, static fn(mixed $x): bool => false))->generate(new Random(1));

        Assert::same($inner->calls, 100);
    }
}
