<?php

declare(strict_types=1);

namespace Rasuvaeff\PropertyTesting\Runner;

use Rasuvaeff\PropertyTesting\GaveUpException;

/**
 * Discards exceeded the budget before the requested checks completed.
 *
 * @internal Stabilising for the engine split; becomes `@api` in
 *   property-testing-core 1.0.
 */
final readonly class GaveUp implements PropertyResult
{
    public function __construct(
        public GaveUpException $exception,
        public RunStatistics $statistics,
    ) {}

    #[\Override]
    public function failure(): \Throwable
    {
        return $this->exception;
    }
}
