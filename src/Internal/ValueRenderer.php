<?php

declare(strict_types=1);

namespace Rasuvaeff\PropertyTesting\Internal;

/**
 * Renders an arbitrary value into a compact, human-readable string for
 * counterexample reporting. Unlike a bare `[N element(s)]`/class-name summary it
 * descends into arrays and simple objects so the shrunk value is actually
 * visible, while staying bounded: depth, element count and string length are all
 * capped, special floats and binary strings are labelled, and object recursion
 * is guarded against cycles.
 *
 * @internal
 */
final class ValueRenderer
{
    private const int MAX_DEPTH = 4;
    private const int MAX_ELEMENTS = 8;
    private const int MAX_STRING = 64;

    public static function render(mixed $value): string
    {
        return self::format($value, self::MAX_DEPTH, []);
    }

    /**
     * @param list<int> $seen spl_object_id trail guarding against object cycles.
     */
    private static function format(mixed $value, int $depth, array $seen): string
    {
        return match (true) {
            $value === null => 'null',
            is_bool($value) => $value ? 'true' : 'false',
            is_int($value) => (string) $value,
            is_float($value) => self::float($value),
            is_string($value) => self::string($value),
            is_array($value) => self::array($value, $depth, $seen),
            is_object($value) => self::object($value, $depth, $seen),
            default => get_debug_type($value),
        };
    }

    private static function float(float $value): string
    {
        // (string) already yields 'NAN', 'INF' and '-INF'; only negative zero
        // needs help — it collapses to "0". Detect it with fdiv() (the `/`
        // operator throws DivisionByZeroError on a zero divisor).
        if ($value === 0.0 && fdiv(1.0, $value) === -INF) {
            return '-0.0';
        }

        return (string) $value;
    }

    private static function string(string $value): string
    {
        if (!mb_check_encoding($value, 'UTF-8')) {
            $bytes = strlen($value);
            $hex = bin2hex(substr($value, 0, self::MAX_STRING));

            return sprintf('b"0x%s%s" (%d byte(s))', $hex, $bytes > self::MAX_STRING ? '…' : '', $bytes);
        }

        if (mb_strlen($value, 'UTF-8') > self::MAX_STRING) {
            return sprintf('"%s…" (%d chars)', self::escape(mb_substr($value, 0, self::MAX_STRING, 'UTF-8')), mb_strlen($value, 'UTF-8'));
        }

        return '"' . self::escape($value) . '"';
    }

    /**
     * Escape the quote, backslash and control characters so a value containing
     * `"`, newlines or tabs stays on one unambiguous line instead of breaking the
     * counterexample across lines. Multibyte (>= 0x80) bytes are left untouched.
     */
    private static function escape(string $value): string
    {
        return addcslashes($value, "\0..\37\"\\\177");
    }

    /**
     * @param array<array-key, mixed> $value
     * @param list<int> $seen
     */
    private static function array(array $value, int $depth, array $seen): string
    {
        if ($value === []) {
            return '[]';
        }

        if ($depth <= 0) {
            return sprintf('[… %d element(s)]', count($value));
        }

        $isList = array_is_list($value);
        $rendered = [];
        $shown = 0;

        /** @var mixed $item */
        foreach ($value as $key => $item) {
            if ($shown >= self::MAX_ELEMENTS) {
                break;
            }

            $rendered[] = $isList
                ? self::format($item, $depth - 1, $seen)
                : self::keyLabel($key) . ' => ' . self::format($item, $depth - 1, $seen);
            ++$shown;
        }

        $remaining = count($value) - $shown;
        if ($remaining > 0) {
            $rendered[] = sprintf('… +%d more', $remaining);
        }

        return '[' . implode(', ', $rendered) . ']';
    }

    private static function keyLabel(int|string $key): string
    {
        return is_int($key) ? (string) $key : '"' . $key . '"';
    }

    /**
     * @param list<int> $seen
     */
    private static function object(object $value, int $depth, array $seen): string
    {
        if ($value instanceof \UnitEnum) {
            return $value::class . '::' . $value->name;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d H:i:s.uP');
        }

        // Stringable includes the package's own CommandSequence/Command traces —
        // render their string form verbatim (unquoted, in full) so the trace
        // reads naturally.
        if ($value instanceof \Stringable) {
            return (string) $value;
        }

        $id = spl_object_id($value);
        if (in_array($id, $seen, true)) {
            return $value::class . ' {*recursion*}';
        }

        $properties = self::objectProperties($value);
        if ($properties === []) {
            return $value::class . ' {}';
        }

        if ($depth <= 0) {
            return $value::class . ' {…}';
        }

        $seen[] = $id;
        $rendered = [];
        $shown = 0;

        /** @var mixed $item */
        foreach ($properties as $name => $item) {
            if ($shown >= self::MAX_ELEMENTS) {
                break;
            }

            $rendered[] = $name . ': ' . self::format($item, $depth - 1, $seen);
            ++$shown;
        }

        $remaining = count($properties) - $shown;
        if ($remaining > 0) {
            $rendered[] = sprintf('… +%d more', $remaining);
        }

        return $value::class . ' {' . implode(', ', $rendered) . '}';
    }

    /**
     * All non-static instance properties, including private and protected ones
     * (typical of constructor-promoted DTOs) that {@see get_object_vars()} would
     * hide when called from outside the class. Uninitialised typed properties are
     * shown as a marker rather than read (which would throw).
     *
     * @return array<string, mixed>
     */
    private static function objectProperties(object $value): array
    {
        $instanceProperties = array_filter(
            (new \ReflectionObject($value))->getProperties(),
            static fn(\ReflectionProperty $property): bool => !$property->isStatic(),
        );

        // Built via array_combine (not per-key assignment) so the mixed property
        // values do not trip Psalm's MixedAssignment at errorLevel 1.
        return array_combine(
            array_map(
                static fn(\ReflectionProperty $property): string => $property->getName(),
                $instanceProperties,
            ),
            array_map(
                static fn(\ReflectionProperty $property): mixed => $property->isInitialized($value)
                    ? $property->getValue($value)
                    : '<uninitialized>',
                $instanceProperties,
            ),
        );
    }
}
