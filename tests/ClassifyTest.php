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
}
