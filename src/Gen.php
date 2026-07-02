<?php

declare(strict_types=1);

namespace Rasuvaeff\PropertyTesting;

use Closure;
use DateTimeImmutable;
use Rasuvaeff\PropertyTesting\Arbitrary\ArrayArbitrary;
use Rasuvaeff\PropertyTesting\Arbitrary\BoolArbitrary;
use Rasuvaeff\PropertyTesting\Arbitrary\BytesArbitrary;
use Rasuvaeff\PropertyTesting\Arbitrary\CharsetStringArbitrary;
use Rasuvaeff\PropertyTesting\Arbitrary\ConstantArbitrary;
use Rasuvaeff\PropertyTesting\Arbitrary\DateTimeArbitrary;
use Rasuvaeff\PropertyTesting\Arbitrary\DictionaryArbitrary;
use Rasuvaeff\PropertyTesting\Arbitrary\FilteredArbitrary;
use Rasuvaeff\PropertyTesting\Arbitrary\FlatMappedArbitrary;
use Rasuvaeff\PropertyTesting\Arbitrary\FloatArbitrary;
use Rasuvaeff\PropertyTesting\Arbitrary\FrequencyArbitrary;
use Rasuvaeff\PropertyTesting\Arbitrary\IntArbitrary;
use Rasuvaeff\PropertyTesting\Arbitrary\MappedArbitrary;
use Rasuvaeff\PropertyTesting\Arbitrary\NullableArbitrary;
use Rasuvaeff\PropertyTesting\Arbitrary\OneOfArbitrary;
use Rasuvaeff\PropertyTesting\Arbitrary\RecordArbitrary;
use Rasuvaeff\PropertyTesting\Arbitrary\StringArbitrary;
use Rasuvaeff\PropertyTesting\Arbitrary\TupleArbitrary;
use Rasuvaeff\PropertyTesting\Arbitrary\UniqueArrayArbitrary;
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
     * Strings whose characters come from a fixed alphabet (split per Unicode
     * codepoint). Shrinks by length toward '', then each character toward the
     * first alphabet character — list simpler characters first.
     */
    public static function stringFrom(string $alphabet, int $minLength = 0, int $maxLength = 100): CharsetStringArbitrary
    {
        return new CharsetStringArbitrary($alphabet, $minLength, $maxLength);
    }

    /**
     * Raw byte strings (every byte 0..255). Shrinks by length toward '', then
     * each byte toward "\x00".
     */
    public static function bytes(int $minLength = 0, int $maxLength = 100): BytesArbitrary
    {
        return new BytesArbitrary($minLength, $maxLength);
    }

    /**
     * Lists whose elements are drawn from $element.
     */
    public static function arrayOf(ArbitraryInterface $element, int $minSize = 0, int $maxSize = 100): ArrayArbitrary
    {
        return new ArrayArbitrary($element, $minSize, $maxSize);
    }

    /**
     * Non-empty lists whose elements are drawn from $element.
     */
    public static function nonEmptyArrayOf(ArbitraryInterface $element, int $maxSize = 100): ArrayArbitrary
    {
        return new ArrayArbitrary($element, 1, $maxSize);
    }

    /**
     * Lists of pairwise-distinct elements (strict comparison) drawn from
     * $element. Element shrinking keeps the list distinct; the result may be
     * smaller than the drawn size when the element space runs out of fresh
     * values (but never below $minSize, which throws instead).
     */
    public static function uniqueArrayOf(ArbitraryInterface $element, int $minSize = 0, int $maxSize = 100): UniqueArrayArbitrary
    {
        return new UniqueArrayArbitrary($element, $minSize, $maxSize);
    }

    /**
     * Associative arrays (maps) with keys from $key and values from $value.
     * Keys must be int or string; colliding keys overwrite, so the result may
     * be smaller than the drawn size.
     */
    public static function dictOf(ArbitraryInterface $key, ArbitraryInterface $value, int $minSize = 0, int $maxSize = 100): DictionaryArbitrary
    {
        return new DictionaryArbitrary($key, $value, $minSize, $maxSize);
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
     * One case of a PHP enum, in declaration order. Shrinks toward
     * earlier-declared cases, so declare simpler cases first.
     *
     * @param class-string $enum
     */
    public static function enum(string $enum): OneOfArbitrary
    {
        if (!enum_exists($enum)) {
            throw new \InvalidArgumentException(sprintf('"%s" is not an enum', $enum));
        }

        $cases = array_map(
            static fn(\ReflectionEnumUnitCase $case): \UnitEnum => $case->getValue(),
            (new \ReflectionEnum($enum))->getCases(),
        );

        return new OneOfArbitrary(...$cases);
    }

    /**
     * Special float values (NaN, ±INF, -0.0 and the representation edges) where
     * float bugs cluster — an opt-in complement to {@see float()}, which stays
     * inside its finite range. Shrinks toward earlier-listed specials.
     */
    public static function floatSpecial(): OneOfArbitrary
    {
        // NAN/INF are produced via fdiv(): Psalm crashes on the NAN constant
        // (Psalm\Type::getFloat(NAN)), and the values are identical.
        return new OneOfArbitrary(
            fdiv(0.0, 0.0),   // NAN
            fdiv(1.0, 0.0),   // INF
            fdiv(-1.0, 0.0),  // -INF
            -0.0,
            PHP_FLOAT_EPSILON,
            PHP_FLOAT_MIN,
            PHP_FLOAT_MAX,
        );
    }

    /**
     * Ordered integer pairs `[lo, hi]` with $min <= lo <= hi <= $max — the
     * "range/interval" input without an {@see Assume::that()} discard. Built on
     * {@see flatMap()}, so both bounds shrink while `lo <= hi` always holds.
     */
    public static function intRange(int $min, int $max): FlatMappedArbitrary
    {
        return new FlatMappedArbitrary(
            new IntArbitrary($min, $max),
            static function (mixed $lo) use ($max): TupleArbitrary {
                \assert(is_int($lo));

                return new TupleArbitrary(new ConstantArbitrary($lo), new IntArbitrary($lo, $max));
            },
        );
    }

    /**
     * Recursive structures with a bounded depth: $wrap receives the arbitrary
     * for the previous level and returns the next one (e.g. wrap a value in an
     * array). At every level generation picks the leaf or the wrapped branch
     * with equal odds, so nesting is possible but not forced. Keep the branch
     * fan-out small (bounded array sizes) — breadth multiplies per level.
     *
     * @param Closure(ArbitraryInterface): ArbitraryInterface $wrap
     */
    public static function recursive(ArbitraryInterface $leaf, Closure $wrap, int $maxDepth = 3): ArbitraryInterface
    {
        if ($maxDepth < 1) {
            throw new \InvalidArgumentException('Max depth must be greater than or equal to 1');
        }

        $arbitrary = $leaf;

        for ($depth = 0; $depth < $maxDepth; ++$depth) {
            /** @var mixed $wrapped */
            $wrapped = ($wrap)($arbitrary);

            if (!$wrapped instanceof ArbitraryInterface) {
                throw new \InvalidArgumentException(sprintf(
                    'recursive() wrap closure must return an ArbitraryInterface, got %s',
                    get_debug_type($wrapped),
                ));
            }

            $arbitrary = new FrequencyArbitrary([[1, $leaf], [1, $wrapped]]);
        }

        return $arbitrary;
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
     * Shrinking happens in the source domain and the function is re-applied,
     * so mapped values shrink through the inner arbitrary's tree.
     *
     * @param Closure(mixed): mixed $map
     */
    public static function map(ArbitraryInterface $inner, Closure $map): MappedArbitrary
    {
        return new MappedArbitrary($inner, $map);
    }

    /**
     * Dependent generators (aka `bind`): feeds each value produced by $inner
     * into $flatMap, which returns the arbitrary generating the final value.
     * Use it when one input's domain depends on another (e.g. an array plus a
     * valid index into it) instead of discarding invalid combinations with
     * {@see Assume::that()}.
     *
     * @param Closure(mixed): ArbitraryInterface $flatMap
     */
    public static function flatMap(ArbitraryInterface $inner, Closure $flatMap): FlatMappedArbitrary
    {
        return new FlatMappedArbitrary($inner, $flatMap);
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
     * stays within the branch that generated the value.
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
            static fn(int $i): mixed => $arbitrary->generate($random)->value,
            range(1, $count),
        );
    }

    /**
     * Eagerly generate one value from $arbitrary for a fixed $seed and collect
     * its first direct shrink candidates. A debugging aid for authors of custom
     * {@see ArbitraryInterface}s: eyeball what the shrink tree offers before
     * wiring the arbitrary into a property.
     *
     * @return array{value: mixed, shrinks: list<mixed>}
     */
    public static function sampleShrinks(ArbitraryInterface $arbitrary, int $seed = 0, int $limit = 10): array
    {
        if ($limit < 1) {
            throw new \InvalidArgumentException('Limit must be greater than or equal to 1');
        }

        $shrinkable = $arbitrary->generate(new Random($seed));

        /** @var list<Shrinkable> $candidates */
        $candidates = [];
        foreach ($shrinkable->shrinks() as $candidate) {
            $candidates[] = $candidate;

            if (count($candidates) >= $limit) {
                break;
            }
        }

        return [
            'value' => $shrinkable->value,
            'shrinks' => array_map(static fn(Shrinkable $candidate): mixed => $candidate->value, $candidates),
        ];
    }
}
