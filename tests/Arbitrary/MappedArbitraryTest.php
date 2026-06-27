<?php

declare(strict_types=1);

namespace Rasuvaeff\PropertyTesting\Tests\Arbitrary;

use Rasuvaeff\PropertyTesting\Arbitrary\IntArbitrary;
use Rasuvaeff\PropertyTesting\Arbitrary\MappedArbitrary;
use Rasuvaeff\PropertyTesting\Random;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Test;

#[Test]
#[Covers(MappedArbitrary::class)]
final class MappedArbitraryTest
{
    public function generateAppliesTheMapping(): void
    {
        $arbitrary = new MappedArbitrary(new IntArbitrary(1, 10), static fn(int $x): int => $x * 2);
        $random = new Random(1);

        for ($i = 0; $i < 100; ++$i) {
            $value = $arbitrary->generate($random);

            Assert::true($value >= 2 && $value <= 20 && $value % 2 === 0);
        }
    }

    public function mappedDomainDoesNotShrink(): void
    {
        $arbitrary = new MappedArbitrary(new IntArbitrary(), static fn(int $x): int => $x);

        Assert::same(iterator_to_array($arbitrary->shrink(5)), []);
    }
}
