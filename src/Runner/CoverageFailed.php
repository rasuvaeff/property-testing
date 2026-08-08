<?php

declare(strict_types=1);

namespace Rasuvaeff\PropertyTesting\Runner;

use Rasuvaeff\PropertyTesting\CoverageViolationException;

/**
 * Every run passed, but a `Classify::cover()` requirement was under-covered —
 * the pass is (partially) vacuous.
 *
 * @internal Stabilising for the engine split; becomes `@api` in
 *   property-testing-core 1.0.
 */
final readonly class CoverageFailed implements PropertyResult
{
    public function __construct(
        public CoverageViolationException $exception,
        public RunStatistics $statistics,
    ) {}

    #[\Override]
    public function failure(): \Throwable
    {
        return $this->exception;
    }
}
