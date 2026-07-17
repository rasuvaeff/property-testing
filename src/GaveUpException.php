<?php

declare(strict_types=1);

namespace Rasuvaeff\PropertyTesting;

use RuntimeException;

/**
 * Thrown (as the failure of a property) when the run loop finished without a
 * single successful check because every run was discarded via
 * {@see Assume::that()} (or a filter-discard). Zero checks means the body
 * asserted nothing, so reporting the property as passed would be vacuous — it is
 * a failure instead.
 *
 * The fix is almost always to construct valid inputs directly (e.g.
 * {@see Gen::flatMap()} / {@see Gen::draw()}) rather than generating broadly and
 * discarding, so runs are valid by construction.
 *
 * @api
 */
final class GaveUpException extends RuntimeException {}
