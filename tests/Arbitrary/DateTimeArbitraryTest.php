<?php

declare(strict_types=1);

namespace Rasuvaeff\PropertyTesting\Tests\Arbitrary;

use DateTimeImmutable;
use Rasuvaeff\PropertyTesting\Arbitrary\DateTimeArbitrary;
use Rasuvaeff\PropertyTesting\Random;
use Testo\Assert;
use Testo\Assert\ExpectException;
use Testo\Codecov\Covers;
use Testo\Test;

#[Test]
#[Covers(DateTimeArbitrary::class)]
final class DateTimeArbitraryTest
{
    public function generateStaysWithinTheConfiguredRange(): void
    {
        $min = new DateTimeImmutable('@1000');
        $max = new DateTimeImmutable('@2000');
        $arbitrary = new DateTimeArbitrary($min, $max);
        $random = new Random(1);

        for ($i = 0; $i < 200; ++$i) {
            $timestamp = $arbitrary->generate($random)->getTimestamp();

            Assert::true($timestamp >= 1000 && $timestamp <= 2000);
        }
    }

    public function generateReachesBothExactBounds(): void
    {
        $arbitrary = new DateTimeArbitrary(new DateTimeImmutable('@10'), new DateTimeImmutable('@13'));
        $random = new Random(1);
        $min = PHP_INT_MAX;
        $max = PHP_INT_MIN;

        for ($i = 0; $i < 200; ++$i) {
            $timestamp = $arbitrary->generate($random)->getTimestamp();
            $min = min($min, $timestamp);
            $max = max($max, $timestamp);
        }

        Assert::same($min, 10);
        Assert::same($max, 13);
    }

    public function shrinkMovesTowardTheEpoch(): void
    {
        $candidates = iterator_to_array(
            (new DateTimeArbitrary())->shrink(new DateTimeImmutable('@500')),
            false,
        );

        Assert::same(count($candidates), 1);
        Assert::same($candidates[0]->getTimestamp(), 0);
    }

    public function shrinkClampsTheEpochIntoTheRange(): void
    {
        // A range that excludes the epoch shrinks toward the nearest bound.
        $candidates = iterator_to_array(
            (new DateTimeArbitrary(new DateTimeImmutable('@1000'), new DateTimeImmutable('@2000')))
                ->shrink(new DateTimeImmutable('@1500')),
            false,
        );

        Assert::same($candidates[0]->getTimestamp(), 1000);
    }

    public function shrinkOfTheTargetYieldsNothing(): void
    {
        Assert::same(
            iterator_to_array((new DateTimeArbitrary())->shrink(new DateTimeImmutable('@0'))),
            [],
        );
    }

    public function shrinkOfNonDateTimeYieldsNothing(): void
    {
        Assert::same(iterator_to_array((new DateTimeArbitrary())->shrink('not a date')), []);
    }

    public function shrinkTargetsTheEpochWhenItIsInsideTheRange(): void
    {
        // A range spanning the epoch shrinks exactly to timestamp 0.
        $candidates = iterator_to_array(
            (new DateTimeArbitrary(new DateTimeImmutable('@-100'), new DateTimeImmutable('@100')))
                ->shrink(new DateTimeImmutable('@50')),
            false,
        );

        Assert::same($candidates[0]->getTimestamp(), 0);
    }

    public function acceptsADegenerateRange(): void
    {
        // min === max is a valid single-point range and must construct + generate.
        $arbitrary = new DateTimeArbitrary(new DateTimeImmutable('@5'), new DateTimeImmutable('@5'));

        Assert::same($arbitrary->generate(new Random(1))->getTimestamp(), 5);
    }

    #[ExpectException(\InvalidArgumentException::class)]
    public function rejectsInvertedRange(): void
    {
        new DateTimeArbitrary(new DateTimeImmutable('@2000'), new DateTimeImmutable('@1000'));
    }
}
