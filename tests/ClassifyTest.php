<?php

declare(strict_types=1);

namespace Rasuvaeff\PropertyTesting\Tests;

use Rasuvaeff\PropertyTesting\Classify;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Lifecycle\BeforeTest;
use Testo\Test;

#[Test]
#[Covers(Classify::class)]
final class ClassifyTest
{
    #[BeforeTest]
    public function reset(): void
    {
        Classify::beginRun();
        Classify::flushRequirements();
    }

    public function labelRecordsForTheCurrentRun(): void
    {
        Classify::label('even');

        Assert::same(Classify::flushRun(), ['even']);
    }

    public function whenRecordsOnlyWhenTheConditionHolds(): void
    {
        Classify::when(true, 'yes');
        Classify::when(false, 'no');

        Assert::same(Classify::flushRun(), ['yes']);
    }

    public function repeatedLabelCountsOnce(): void
    {
        Classify::label('dup');
        Classify::label('dup');

        Assert::same(Classify::flushRun(), ['dup']);
    }

    public function flushReturnsLabelsThenClearsTheBuffer(): void
    {
        Classify::label('x');

        Assert::same(Classify::flushRun(), ['x']);
        Assert::same(Classify::flushRun(), []);
    }

    public function beginRunClearsBufferedLabels(): void
    {
        Classify::label('stale');
        Classify::beginRun();

        Assert::same(Classify::flushRun(), []);
    }

    public function coverRegistersTheRequirementRegardlessOfTheCondition(): void
    {
        Classify::cover(false, 'rare', 25.0);

        Assert::same(Classify::flushRun(), []);
        Assert::same(Classify::flushRequirements(), ['rare' => 25.0]);
    }

    public function coverRecordsTheLabelOnlyWhenTheConditionHolds(): void
    {
        Classify::cover(true, 'hit', 10.0);
        Classify::cover(false, 'miss', 10.0);

        Assert::same(Classify::flushRun(), ['hit']);
        Assert::same(Classify::flushRequirements(), ['hit' => 10.0, 'miss' => 10.0]);
    }

    public function coverKeepsTheLastRequirementForALabel(): void
    {
        Classify::cover(true, 'even', 10.0);
        Classify::cover(true, 'even', 30.0);

        Assert::same(Classify::flushRequirements(), ['even' => 30.0]);
    }

    public function flushRequirementsClearsTheRegistry(): void
    {
        Classify::cover(true, 'once', 5.0);

        Assert::same(Classify::flushRequirements(), ['once' => 5.0]);
        Assert::same(Classify::flushRequirements(), []);
    }

    public function coverRequirementsSurviveBeginRun(): void
    {
        // Requirements are per-property, not per-run: a new run must not lose them.
        Classify::cover(true, 'kept', 5.0);
        Classify::beginRun();

        Assert::same(Classify::flushRequirements(), ['kept' => 5.0]);
    }

    public function coverAcceptsTheZeroAndHundredBoundaries(): void
    {
        Classify::cover(true, 'floor', 0.0);
        Classify::cover(true, 'ceiling', 100.0);

        Assert::same(Classify::flushRequirements(), ['floor' => 0.0, 'ceiling' => 100.0]);
    }

    public function flushRunReturnsEveryLabelOfTheRun(): void
    {
        Classify::label('first');
        Classify::label('second');

        Assert::same(Classify::flushRun(), ['first', 'second']);
    }

    public function coverRejectsANegativePercentage(): void
    {
        try {
            Classify::cover(true, 'bad', -1.0);

            Assert::fail('expected an InvalidArgumentException');
        } catch (\InvalidArgumentException $e) {
            Assert::string($e->getMessage())->contains('between 0 and 100');
        }
    }

    public function coverRejectsAPercentageAboveOneHundred(): void
    {
        try {
            Classify::cover(true, 'bad', 100.1);

            Assert::fail('expected an InvalidArgumentException');
        } catch (\InvalidArgumentException $e) {
            Assert::string($e->getMessage())->contains('between 0 and 100');
        }
    }
}
