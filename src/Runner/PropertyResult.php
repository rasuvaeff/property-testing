<?php

declare(strict_types=1);

namespace Rasuvaeff\PropertyTesting\Runner;

/**
 * Structured outcome of a property run. The runner returns a result — it
 * never throws for a property outcome, exits, or prints; how a failure
 * surfaces (a Testo `TestResult`, a PHPUnit assertion error, an exit code) is
 * the adapter's decision.
 *
 * The hierarchy is closed by construction: one class per outcome, so
 * impossible data combinations are not representable. Configuration errors
 * (invalid runs, a missing generator) remain exceptions — they are programmer
 * errors, not verdicts about the property.
 *
 * @internal Stabilising for the engine split; becomes `@api` in
 *   property-testing-core 1.0.
 */
interface PropertyResult
{
    /**
     * The engine exception describing the failure — the same type and message
     * the package has always reported ({@see \Rasuvaeff\PropertyTesting\PropertyViolationException}
     * for a falsification, and so on). Null for a pass.
     */
    public function failure(): ?\Throwable;
}
