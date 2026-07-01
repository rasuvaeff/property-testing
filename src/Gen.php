<?php

declare(strict_types=1);

namespace Rasuvaeff\PropertyTesting;

use Closure;
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

/**
 * Facade with static factories for the built-in {@see ArbitraryInterface}s.
 *
 * Each factory returns a ready-to-use arbitrary; values are never generated
 * directly through Gen — that happens inside the property runner, which threads
 * the seedable {@see Random} through every generator so runs are reproducible.
 * The one exception is {@see sample()}, a debugging aid that eagerly generates
 * values from a given arbitrary.
 *
 * @api
 */
final class Gen
{
    private function __construct()
    {
        // Static facade; not instantiable.
    }

    /**
     * Integers spanning PHP_INT_MIN..PHP_INT_MAX.
     */
    public static function int(): IntArbitrary
    {
        return new IntArbitrary();
    }

    /**
     * @param int<min, max> $min
     * @param int<min, max> $max
     */
    public static function intBetween(int $min, int $max): IntArbitrary
    {
        return new IntArbitrary($min, $max);
    }

    /**
     * Positive integers (1..PHP_INT_MAX).
     */
    public static function intPositive(): IntArbitrary
    {
        return new IntArbitrary(1, PHP_INT_MAX);
    }

    /**
     * Floats in the half-open range [0.0, 1.0).
     */
    public static function float(): FloatArbitrary
    {
        return new FloatArbitrary(0.0, 1.0);
    }

    public static function floatBetween(float $min, float $max): FloatArbitrary
    {
        return new FloatArbitrary($min, $max);
    }

    public static function bool(): BoolArbitrary
    {
        return new BoolArbitrary();
    }

    /**
     * Unicode strings of length 0..100.
     */
    public static function string(): StringArbitrary
    {
        return new StringArbitrary(0, 100, unicode: true);
    }

    /**
     * Printable ASCII strings of length 0..100.
     */
    public static function stringAscii(): StringArbitrary
    {
        return new StringArbitrary(0, 100, unicode: false);
    }

    /**
     * @param int<0, max> $minLength
     * @param int<1, max> $maxLength
     */
    public static function stringOf(int $minLength, int $maxLength): StringArbitrary
    {
        return new StringArbitrary($minLength, $maxLength, unicode: true);
    }

    /**
     * A single printable ASCII character.
     */
    public static function char(): StringArbitrary
    {
        return new StringArbitrary(1, 1, unicode: false);
    }

    /**
     * Lists whose elements are drawn from $element. Size 0..100.
     */
    public static function arrayOf(ArbitraryInterface $element): ArrayArbitrary
    {
        return new ArrayArbitrary($element, 0, 100);
    }

    /**
     * Non-empty lists (size 1..100) whose elements are drawn from $element.
     */
    public static function nonEmptyArrayOf(ArbitraryInterface $element): ArrayArbitrary
    {
        return new ArrayArbitrary($element, 1, 100);
    }

    /**
     * Associative arrays (maps) of size 0..100 with keys from $key and values
     * from $value. Keys must be int or string; colliding keys overwrite, so the
     * result may be smaller than the drawn size.
     */
    public static function dictOf(ArbitraryInterface $key, ArbitraryInterface $value): DictionaryArbitrary
    {
        return new DictionaryArbitrary($key, $value, 0, 100);
    }

    /**
     * Fixed-shape associative array: each field is generated from its own
     * arbitrary, keyed by field name. The property receives a single string-keyed
     * array; shrinking reduces each field through its arbitrary while keeping the
     * key set fixed.
     *
     * @param array<string, ArbitraryInterface> $shape Field name => arbitrary.
     */
    public static function record(array $shape): RecordArbitrary
    {
        return new RecordArbitrary($shape);
    }

    /**
     * Picks one of the given values at random.
     */
    public static function oneOf(mixed ...$values): OneOfArbitrary
    {
        return new OneOfArbitrary(...$values);
    }

    /**
     * Picks one value at random from an array (the array form of {@see oneOf()}).
     *
     * @param array<array-key, mixed> $values Must be non-empty.
     */
    public static function elements(array $values): OneOfArbitrary
    {
        return new OneOfArbitrary(...array_values($values));
    }

    /**
     * Always produces $value; does not shrink.
     */
    public static function constant(mixed $value): ConstantArbitrary
    {
        return new ConstantArbitrary($value);
    }

    /**
     * Yields null or a value from $inner with roughly even odds.
     */
    public static function nullable(ArbitraryInterface $inner): NullableArbitrary
    {
        return new NullableArbitrary($inner);
    }

    /**
     * Transforms each value produced by $inner through a pure function.
     *
     * @param Closure(mixed): mixed $map
     */
    public static function map(ArbitraryInterface $inner, Closure $map): MappedArbitrary
    {
        return new MappedArbitrary($inner, $map);
    }

    /**
     * Generates values from $inner, retrying until $predicate holds.
     *
     * @param Closure(mixed): bool $predicate
     */
    public static function filter(ArbitraryInterface $inner, Closure $predicate): FilteredArbitrary
    {
        return new FilteredArbitrary($inner, $predicate);
    }

    /**
     * Fixed-arity tuple: one value per element arbitrary, in order. The property
     * receives the tuple as a single array argument; shrinking reduces each
     * position through its own arbitrary while keeping the arity fixed.
     */
    public static function tuple(ArbitraryInterface ...$elements): TupleArbitrary
    {
        return new TupleArbitrary(...$elements);
    }

    /**
     * Weighted choice among `[weight, arbitrary]` pairs: a branch is picked with
     * probability proportional to its weight, then produces the value. Shrinking
     * delegates to every inner arbitrary.
     *
     * @param iterable<array{int, ArbitraryInterface}> $pairs Weights must be >= 1.
     */
    public static function frequency(iterable $pairs): FrequencyArbitrary
    {
        return new FrequencyArbitrary($pairs);
    }

    /**
     * Canonical RFC 4122 version 4 UUID strings. Does not shrink.
     */
    public static function uuid(): UuidArbitrary
    {
        return new UuidArbitrary();
    }

    /**
     * UTC {@see DateTimeImmutable} values with a timestamp in the inclusive range
     * `[$min, $max]` (defaults: 1970-01-01 .. 2100-01-01). Shrinks toward the
     * Unix epoch, clamped to the range.
     */
    public static function datetime(?DateTimeImmutable $min = null, ?DateTimeImmutable $max = null): DateTimeArbitrary
    {
        return new DateTimeArbitrary($min, $max);
    }

    /**
     * Eagerly generate $count values from $arbitrary using a fixed $seed. A
     * debugging aid for inspecting a generator's output and distribution; unlike
     * the other factories it returns values, not an arbitrary.
     *
     * @return list<mixed>
     */
    public static function sample(ArbitraryInterface $arbitrary, int $count = 10, int $seed = 0): array
    {
        if ($count < 1) {
            throw new \InvalidArgumentException('Count must be greater than or equal to 1');
        }

        $random = new Random($seed);

        return array_map(
            static fn(int $i): mixed => $arbitrary->generate($random),
            range(1, $count),
        );
    }
}
