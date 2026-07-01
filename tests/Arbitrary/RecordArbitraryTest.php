<?php

declare(strict_types=1);

namespace Rasuvaeff\PropertyTesting\Tests\Arbitrary;

use Rasuvaeff\PropertyTesting\Arbitrary\BoolArbitrary;
use Rasuvaeff\PropertyTesting\Arbitrary\IntArbitrary;
use Rasuvaeff\PropertyTesting\Arbitrary\RecordArbitrary;
use Rasuvaeff\PropertyTesting\Random;
use Testo\Assert;
use Testo\Assert\ExpectException;
use Testo\Codecov\Covers;
use Testo\Test;

#[Test]
#[Covers(RecordArbitrary::class)]
final class RecordArbitraryTest
{
    public function generateProducesOneValuePerFieldKeyedByName(): void
    {
        $arbitrary = new RecordArbitrary([
            'age' => new IntArbitrary(30, 30),
            'active' => new BoolArbitrary(),
        ]);

        $record = $arbitrary->generate(new Random(1));

        Assert::same($record['age'], 30);
        Assert::true(is_bool($record['active']));
        Assert::same(array_keys($record), ['age', 'active']);
    }

    public function generateDrawsEachFieldFromItsOwnArbitrary(): void
    {
        $arbitrary = new RecordArbitrary([
            'x' => new IntArbitrary(7, 7),
            'y' => new IntArbitrary(9, 9),
        ]);

        Assert::same($arbitrary->generate(new Random(1)), ['x' => 7, 'y' => 9]);
    }

    public function shrinkReducesEachFieldThroughItsArbitrary(): void
    {
        $candidates = iterator_to_array(
            (new RecordArbitrary([
                'x' => new IntArbitrary(0, 10),
                'y' => new IntArbitrary(0, 10),
            ]))->shrink(['x' => 8, 'y' => 8]),
            false,
        );

        Assert::true(in_array(['x' => 0, 'y' => 8], $candidates, true));
        Assert::true(in_array(['x' => 8, 'y' => 0], $candidates, true));
    }

    public function shrinkKeepsTheKeySetFixed(): void
    {
        $candidates = iterator_to_array(
            (new RecordArbitrary([
                'x' => new IntArbitrary(0, 10),
                'y' => new IntArbitrary(0, 10),
            ]))->shrink(['x' => 8, 'y' => 8]),
            false,
        );

        foreach ($candidates as $candidate) {
            Assert::same(array_keys($candidate), ['x', 'y']);
        }
    }

    public function shrinkSkipsFieldsMissingFromTheValueAndContinues(): void
    {
        // The first shape field 'x' is absent from the value: shrinking must skip
        // it (continue, not break) and still reach the present 'y'.
        $candidates = iterator_to_array(
            (new RecordArbitrary([
                'x' => new IntArbitrary(0, 10),
                'y' => new IntArbitrary(0, 10),
            ]))->shrink(['y' => 8]),
            false,
        );

        Assert::true(in_array(['y' => 0], $candidates, true));
    }

    public function shrinkOfNonArrayYieldsNothing(): void
    {
        Assert::same(iterator_to_array((new RecordArbitrary(['x' => new IntArbitrary()]))->shrink(42)), []);
    }

    #[ExpectException(\InvalidArgumentException::class)]
    public function rejectsEmptyShape(): void
    {
        new RecordArbitrary([]);
    }
}
