<?php

declare(strict_types=1);

namespace Rasuvaeff\PropertyTesting;

use Closure;
use Rasuvaeff\PropertyTesting\Arbitrary\ArrayArbitrary;
use Rasuvaeff\PropertyTesting\Arbitrary\BoolArbitrary;
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

/**
 * Facade with static factories for the built-in {@see ArbitraryInterface}s.
 *
 * Each factory returns a ready-to-use arbitrary; values are never generated
 * directly through Gen — that happens inside the property runner, which threads
 * the seedable {@see Random} through every generator so runs are reproducible.
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
}
