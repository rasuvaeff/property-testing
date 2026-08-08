<?php

declare(strict_types=1);

namespace Rasuvaeff\PropertyTesting\Runner;

use Rasuvaeff\PropertyTesting\TimeBudgetExceededException;

/**
 * The whole random phase overran its wall-clock budget before the requested
 * checks completed.
 *
 * @internal Stabilising for the engine split; becomes `@api` in
 *   property-testing-core 1.0.
 */
final readonly class TimeBudgetExceeded implements PropertyResult
{
    public function __construct(
        public TimeBudgetExceededException $exception,
        public RunStatistics $statistics,
    ) {}

    #[\Override]
    public function failure(): \Throwable
    {
        return $this->exception;
    }
}
