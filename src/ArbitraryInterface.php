<?php

declare(strict_types=1);

namespace Rasuvaeff\PropertyTesting;

/**
 * Describes a space of random values plus a shrinking strategy for it.
 *
 * An arbitrary produces a value via {@see generate()} and, given a failing
 * value, yields progressively "smaller" candidates via {@see shrink()}. The
 * property runner tries each candidate and keeps shrinking until no smaller
 * value still fails the property, yielding a minimal counterexample.
 *
 * @api
 */
interface ArbitraryInterface
{
    /**
     * Produce one random value from this arbitrary's space.
     */
    public function generate(Random $random): mixed;

    /**
     * Yield candidate values "smaller" than the given one, ordered by
     * likelihood of shrinking the most aggressive first (typically toward a
     * zero/empty/identity element).
     *
     * The runner only consumes candidates until the first one that still fails;
     * ordering them well keeps shrinking fast.
     *
     * @return iterable<mixed>
     */
    public function shrink(mixed $value): iterable;
}
