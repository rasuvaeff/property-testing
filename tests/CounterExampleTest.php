<?php

declare(strict_types=1);

namespace Rasuvaeff\PropertyTesting\Tests;

use Rasuvaeff\PropertyTesting\CounterExample;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Test;

#[Test]
#[Covers(CounterExample::class)]
final class CounterExampleTest
{
    public function storesAllFieldsVerbatim(): void
    {
        $failure = new \RuntimeException('boom');
        $counterExample = new CounterExample(
            seed: 42,
            runsBeforeFailure: 7,
            originalArguments: ['x' => 100],
            shrunkArguments: ['x' => 3],
            failure: $failure,
            skips: 2,
        );

        Assert::same($counterExample->seed, 42);
        Assert::same($counterExample->runsBeforeFailure, 7);
        Assert::same($counterExample->originalArguments, ['x' => 100]);
        Assert::same($counterExample->shrunkArguments, ['x' => 3]);
        Assert::same($counterExample->failure, $failure);
        Assert::same($counterExample->skips, 2);
    }

    public function defaultsFailureAndSkipsToNullAndZero(): void
    {
        $counterExample = new CounterExample(
            seed: 1,
            runsBeforeFailure: 1,
            originalArguments: [],
            shrunkArguments: [],
        );

        Assert::null($counterExample->failure);
        Assert::same($counterExample->skips, 0);
    }
}
