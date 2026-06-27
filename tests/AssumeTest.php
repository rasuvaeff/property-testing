<?php

declare(strict_types=1);

namespace Rasuvaeff\PropertyTesting\Tests;

use Rasuvaeff\PropertyTesting\Assume;
use Rasuvaeff\PropertyTesting\AssumptionSkipped;
use Testo\Assert;
use Testo\Assert\ExpectException;
use Testo\Codecov\Covers;
use Testo\Test;

#[Test]
#[Covers(Assume::class)]
final class AssumeTest
{
    public function trueConditionDoesNotThrow(): void
    {
        Assume::that(true);

        Assert::true(true);
    }

    #[ExpectException(AssumptionSkipped::class)]
    public function falseConditionThrowsAssumptionSkipped(): void
    {
        Assume::that(false);
    }
}
