<?php

declare(strict_types=1);

namespace Rasuvaeff\PropertyTesting\Tests;

use DateTimeImmutable;
use Rasuvaeff\PropertyTesting\Arbitrary\ArrayArbitrary;
use Rasuvaeff\PropertyTesting\Arbitrary\BoolArbitrary;
use Rasuvaeff\PropertyTesting\Arbitrary\ConstantArbitrary;
use Rasuvaeff\PropertyTesting\Arbitrary\DateTimeArbitrary;
use Rasuvaeff\PropertyTesting\Arbitrary\DictionaryArbitrary;
use Rasuvaeff\PropertyTesting\Arbitrary\FilteredArbitrary;
use Rasuvaeff\PropertyTesting\Arbitrary\FloatArbitrary;
use Rasuvaeff\PropertyTesting\Arbitrary\FrequencyArbitrary;
use Rasuvaeff\PropertyTesting\Arbitrary\IntArbitrary;
use Rasuvaeff\PropertyTesting\Arbitrary\MappedArbitrary;
use Rasuvaeff\PropertyTesting\Arbitrary\NullableArbitrary;
use Rasuvaeff\PropertyTesting\Arbitrary\OneOfArbitrary;
use Rasuvaeff\PropertyTesting\Arbitrary\RecordArbitrary;
use Rasuvaeff\PropertyTesting\Arbitrary\StringArbitrary;
use Rasuvaeff\PropertyTesting\Arbitrary\TupleArbitrary;
use Rasuvaeff\PropertyTesting\Arbitrary\UuidArbitrary;
use Rasuvaeff\PropertyTesting\Gen;
use Rasuvaeff\PropertyTesting\Random;
use Testo\Assert;
use Testo\Assert\ExpectException;
use Testo\Codecov\Covers;
use Testo\Test;

#[Test]
#[Covers(Gen::class)]
final class GenTest
{
    public function intReturnsUnboundedIntArbitrary(): void
    {
        Assert::instanceOf(Gen::int(), IntArbitrary::class);
    }

    public function intPositiveStartsAtOne(): void
    {
        Assert::instanceOf(Gen::intPositive(), IntArbitrary::class);
    }

    public function intBetweenAcceptsCustomBounds(): void
    {
        $arbitrary = Gen::intBetween(-5, 5);

        Assert::instanceOf($arbitrary, IntArbitrary::class);
        Assert::same($arbitrary->generate(new \Rasuvaeff\PropertyTesting\Random(1)) >= -5, true);
    }

    public function floatAndFloatBetweenReturnFloatArbitrary(): void
    {
        Assert::instanceOf(Gen::float(), FloatArbitrary::class);
        Assert::instanceOf(Gen::floatBetween(0.0, 10.0), FloatArbitrary::class);
    }

    public function boolReturnsBoolArbitrary(): void
    {
        Assert::instanceOf(Gen::bool(), BoolArbitrary::class);
    }

    public function stringFactoriesReturnStringArbitrary(): void
    {
        Assert::instanceOf(Gen::string(), StringArbitrary::class);
        Assert::instanceOf(Gen::stringAscii(), StringArbitrary::class);
        Assert::instanceOf(Gen::stringOf(2, 8), StringArbitrary::class);
    }

    public function arrayFactoriesReturnArrayArbitrary(): void
    {
        Assert::instanceOf(Gen::arrayOf(Gen::int()), ArrayArbitrary::class);
        Assert::instanceOf(Gen::nonEmptyArrayOf(Gen::int()), ArrayArbitrary::class);
    }

    public function dictOfReturnsDictionaryArbitrary(): void
    {
        Assert::instanceOf(Gen::dictOf(Gen::stringOf(1, 5), Gen::int()), DictionaryArbitrary::class);
    }

    public function dictOfReachesTheEmptyMap(): void
    {
        // The factory must configure minSize 0 so an empty map is reachable.
        $arbitrary = Gen::dictOf(Gen::stringOf(1, 5), Gen::int());
        $random = new Random(1);
        $sawEmpty = false;

        for ($i = 0; $i < 200; ++$i) {
            if ($arbitrary->generate($random) === []) {
                $sawEmpty = true;

                break;
            }
        }

        Assert::true($sawEmpty);
    }

    public function recordReturnsRecordArbitrary(): void
    {
        Assert::instanceOf(Gen::record(['x' => Gen::int(), 'y' => Gen::bool()]), RecordArbitrary::class);
    }

    public function oneOfReturnsOneOfArbitrary(): void
    {
        Assert::instanceOf(Gen::oneOf('a', 'b', 'c'), OneOfArbitrary::class);
    }

    public function elementsReturnsOneOfArbitraryFromAnArray(): void
    {
        $arbitrary = Gen::elements(['a', 'b', 'c']);

        Assert::instanceOf($arbitrary, OneOfArbitrary::class);
        Assert::true(in_array($arbitrary->generate(new Random(1)), ['a', 'b', 'c'], true));
    }

    public function elementsAcceptsAStringKeyedArray(): void
    {
        // Keys are dropped via array_values, so a non-list array is accepted.
        $arbitrary = Gen::elements(['x' => 10, 'y' => 20]);

        Assert::true(in_array($arbitrary->generate(new Random(1)), [10, 20], true));
    }

    public function constantReturnsConstantArbitrary(): void
    {
        $arbitrary = Gen::constant('fixed');

        Assert::instanceOf($arbitrary, ConstantArbitrary::class);
        Assert::same($arbitrary->generate(new Random(1)), 'fixed');
    }

    public function charReturnsSinglePrintableAsciiCharacter(): void
    {
        $arbitrary = Gen::char();

        Assert::instanceOf($arbitrary, StringArbitrary::class);

        $random = new Random(1);
        for ($i = 0; $i < 100; ++$i) {
            $char = $arbitrary->generate($random);
            $code = ord($char);

            Assert::same(strlen($char), 1);
            Assert::true($code >= 32 && $code <= 126);
        }
    }

    public function uuidReturnsUuidArbitrary(): void
    {
        Assert::instanceOf(Gen::uuid(), UuidArbitrary::class);
    }

    public function datetimeReturnsDateTimeArbitrary(): void
    {
        Assert::instanceOf(Gen::datetime(), DateTimeArbitrary::class);
        Assert::instanceOf(
            Gen::datetime(new DateTimeImmutable('@0'), new DateTimeImmutable('@100')),
            DateTimeArbitrary::class,
        );
    }

    public function sampleGeneratesTheRequestedNumberOfValues(): void
    {
        $values = Gen::sample(Gen::intBetween(1, 6), 20, 42);

        Assert::same(count($values), 20);

        foreach ($values as $value) {
            Assert::true(is_int($value) && $value >= 1 && $value <= 6);
        }
    }

    public function sampleIsReproducibleForAGivenSeed(): void
    {
        Assert::same(
            Gen::sample(Gen::intBetween(1, 1_000_000), 10, 7),
            Gen::sample(Gen::intBetween(1, 1_000_000), 10, 7),
        );
    }

    public function sampleAcceptsACountOfOne(): void
    {
        Assert::same(count(Gen::sample(Gen::int(), 1, 0)), 1);
    }

    #[ExpectException(\InvalidArgumentException::class)]
    public function sampleRejectsNonPositiveCount(): void
    {
        Gen::sample(Gen::int(), 0);
    }

    public function nullableWrapsInnerArbitrary(): void
    {
        Assert::instanceOf(Gen::nullable(Gen::int()), NullableArbitrary::class);
    }

    public function mapReturnsMappedArbitrary(): void
    {
        Assert::instanceOf(Gen::map(Gen::int(), static fn(int $x): int => $x * 2), MappedArbitrary::class);
    }

    public function filterReturnsFilteredArbitrary(): void
    {
        Assert::instanceOf(Gen::filter(Gen::int(), static fn(int $x): bool => $x > 0), FilteredArbitrary::class);
    }

    public function tupleReturnsTupleArbitrary(): void
    {
        Assert::instanceOf(Gen::tuple(Gen::int(), Gen::bool()), TupleArbitrary::class);
    }

    public function frequencyReturnsFrequencyArbitrary(): void
    {
        Assert::instanceOf(Gen::frequency([[3, Gen::int()], [1, Gen::bool()]]), FrequencyArbitrary::class);
    }

    public function intPositiveHasLowerBoundOne(): void
    {
        // The clamped-to-zero shrink candidate reveals the configured lower bound.
        $candidates = iterator_to_array(Gen::intPositive()->shrink(8), false);

        Assert::same($candidates[0], 1);
    }

    public function floatSpansTheHalfOpenUnitRange(): void
    {
        $random = new Random(7);
        $sawLow = false;
        $sawHigh = false;

        for ($i = 0; $i < 200; ++$i) {
            $value = Gen::float()->generate($random);

            Assert::true($value >= 0.0 && $value < 1.0);
            $value < 0.5 ? $sawLow = true : $sawHigh = true;
        }

        Assert::true($sawLow);
        Assert::true($sawHigh);
    }

    public function stringIsUnicodeAndSpansLengthZeroToHundred(): void
    {
        $random = new Random(1);
        $sawEmpty = false;
        $sawMultibyte = false;
        $maxLength = 0;

        for ($i = 0; $i < 4000; ++$i) {
            $value = Gen::string()->generate($random);
            $length = mb_strlen($value, 'UTF-8');
            $maxLength = max($maxLength, $length);

            if ($value === '') {
                $sawEmpty = true;
            }
            if (strlen($value) !== $length) {
                $sawMultibyte = true;
            }
        }

        Assert::true($sawEmpty);
        Assert::true($sawMultibyte);
        Assert::same($maxLength, 100);
    }

    public function stringAsciiIsPrintableAsciiAndSpansLengthZeroToHundred(): void
    {
        $random = new Random(1);
        $sawEmpty = false;
        $maxLength = 0;

        for ($i = 0; $i < 4000; ++$i) {
            $value = Gen::stringAscii()->generate($random);
            $maxLength = max($maxLength, strlen($value));

            if ($value === '') {
                $sawEmpty = true;
            }
            foreach (str_split($value === '' ? ' ' : $value) as $char) {
                Assert::true(ord($char) >= 32 && ord($char) <= 126);
            }
        }

        Assert::true($sawEmpty);
        Assert::same($maxLength, 100);
    }

    public function stringOfIsUnicodeAndRespectsBounds(): void
    {
        $random = new Random(1);
        $sawMultibyte = false;

        for ($i = 0; $i < 500; ++$i) {
            $value = Gen::stringOf(3, 6)->generate($random);
            $length = mb_strlen($value, 'UTF-8');

            Assert::true($length >= 3 && $length <= 6);
            if (strlen($value) !== $length) {
                $sawMultibyte = true;
            }
        }

        Assert::true($sawMultibyte);
    }

    public function arrayOfSpansSizeZeroToHundred(): void
    {
        $random = new Random(1);
        $sawEmpty = false;
        $maxSize = 0;

        for ($i = 0; $i < 4000; ++$i) {
            $size = count(Gen::arrayOf(Gen::int())->generate($random));
            $maxSize = max($maxSize, $size);

            if ($size === 0) {
                $sawEmpty = true;
            }
        }

        Assert::true($sawEmpty);
        Assert::same($maxSize, 100);
    }

    public function nonEmptyArrayOfSpansSizeOneToHundred(): void
    {
        $random = new Random(1);
        $minSize = PHP_INT_MAX;
        $maxSize = 0;

        for ($i = 0; $i < 4000; ++$i) {
            $size = count(Gen::nonEmptyArrayOf(Gen::int())->generate($random));
            $minSize = min($minSize, $size);
            $maxSize = max($maxSize, $size);
        }

        Assert::same($minSize, 1);
        Assert::same($maxSize, 100);
    }
}
