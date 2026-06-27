<?php

declare(strict_types=1);

namespace Rasuvaeff\PropertyTesting\Tests\Arbitrary;

use Rasuvaeff\PropertyTesting\Arbitrary\BoolArbitrary;
use Rasuvaeff\PropertyTesting\Arbitrary\IntArbitrary;
use Rasuvaeff\PropertyTesting\Arbitrary\TupleArbitrary;
use Rasuvaeff\PropertyTesting\Random;
use Testo\Assert;
use Testo\Assert\ExpectException;
use Testo\Codecov\Covers;
use Testo\Test;

#[Test]
#[Covers(TupleArbitrary::class)]
final class TupleArbitraryTest
{
    public function generateProducesOneValuePerElementInOrder(): void
    {
        $arbitrary = new TupleArbitrary(new IntArbitrary(5, 5), new IntArbitrary(9, 9));

        Assert::same($arbitrary->generate(new Random(1)), [5, 9]);
    }

    public function generateReindexesNamedVariadicArgumentsToAList(): void
    {
        // Named arguments collect into the variadic string-keyed; the tuple must
        // re-index them to a positional list so the result is a plain list.
        $arbitrary = new TupleArbitrary(first: new IntArbitrary(5, 5), second: new IntArbitrary(9, 9));

        Assert::same($arbitrary->generate(new Random(1)), [5, 9]);
    }

    public function generateLengthMatchesArity(): void
    {
        $arbitrary = new TupleArbitrary(new IntArbitrary(), new IntArbitrary(), new BoolArbitrary());

        Assert::same(count($arbitrary->generate(new Random(1))), 3);
    }

    public function generateDrawsEachElementFromItsOwnArbitrary(): void
    {
        // Heterogeneous elements: an int and a bool, each from its own arbitrary.
        $arbitrary = new TupleArbitrary(new IntArbitrary(42, 42), new BoolArbitrary());
        $tuple = $arbitrary->generate(new Random(1));

        Assert::same($tuple[0], 42);
        Assert::true(is_bool($tuple[1]));
    }

    public function shrinkReducesEachPositionThroughItsElement(): void
    {
        $candidates = iterator_to_array(
            (new TupleArbitrary(new IntArbitrary(0, 10), new IntArbitrary(0, 10)))->shrink([8, 8]),
            false,
        );

        Assert::true(in_array([0, 8], $candidates, true));
        Assert::true(in_array([8, 0], $candidates, true));
    }

    public function shrinkKeepsArityFixed(): void
    {
        $candidates = iterator_to_array(
            (new TupleArbitrary(new IntArbitrary(0, 10), new IntArbitrary(0, 10)))->shrink([8, 8]),
            false,
        );

        foreach ($candidates as $candidate) {
            Assert::same(count($candidate), 2);
        }
    }

    public function shrinkOfMismatchedArityYieldsNothing(): void
    {
        // A two-element tuple cannot shrink a one-element value.
        Assert::same(
            iterator_to_array((new TupleArbitrary(new IntArbitrary(), new IntArbitrary()))->shrink([1])),
            [],
        );
    }

    public function shrinkOfNonArrayYieldsNothing(): void
    {
        Assert::same(iterator_to_array((new TupleArbitrary(new IntArbitrary()))->shrink(42)), []);
    }

    public function shrinkReindexesNonListInputToAList(): void
    {
        // A failing value with non-sequential keys is re-indexed so position i
        // maps to element arbitrary i (matching ArrayArbitrary's behaviour).
        $candidates = iterator_to_array(
            (new TupleArbitrary(new IntArbitrary(0, 10), new IntArbitrary(0, 10)))->shrink([2 => 8, 5 => 8]),
            false,
        );

        Assert::true(in_array([0, 8], $candidates, true));
    }

    #[ExpectException(\InvalidArgumentException::class)]
    public function rejectsEmptyTuple(): void
    {
        new TupleArbitrary();
    }
}
