<?php

declare(strict_types=1);

namespace Rasuvaeff\PropertyTesting\Tests;

use Rasuvaeff\PropertyTesting\Property;
use Testo\Assert;
use Testo\Assert\ExpectException;
use Testo\Codecov\Covers;
use Testo\Test;

#[Test]
#[Covers(Property::class)]
final class PropertyTest
{
    public function defaultsAreSane(): void
    {
        $property = new Property();

        Assert::same($property->runs, 100);
        Assert::null($property->seed);
        Assert::null($property->generators);
        Assert::null($property->maxShrinks);
    }

    public function retainsConstructorArguments(): void
    {
        $property = new Property(runs: 250, seed: 42, generators: 'provide', maxShrinks: 5);

        Assert::same($property->runs, 250);
        Assert::same($property->seed, 42);
        Assert::same($property->generators, 'provide');
        Assert::same($property->maxShrinks, 5);
    }

    public function acceptsZeroMaxShrinks(): void
    {
        Assert::same((new Property(maxShrinks: 0))->maxShrinks, 0);
    }

    public function acceptsRunsOfOne(): void
    {
        Assert::same((new Property(runs: 1))->runs, 1);
    }

    #[ExpectException(\InvalidArgumentException::class)]
    public function rejectsRunsBelowOne(): void
    {
        new Property(runs: 0);
    }

    #[ExpectException(\InvalidArgumentException::class)]
    public function rejectsNegativeMaxShrinks(): void
    {
        new Property(maxShrinks: -1);
    }
}
