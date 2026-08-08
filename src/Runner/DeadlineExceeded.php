<?php

declare(strict_types=1);

namespace Rasuvaeff\PropertyTesting\Runner;

use Rasuvaeff\PropertyTesting\DeadlineExceededException;

/**
 * A single run (random, example or regression replay) took longer than the
 * per-run deadline. The offending input is reported unshrunk.
 *
 * @internal Stabilising for the engine split; becomes `@api` in
 *   property-testing-core 1.0.
 */
final readonly class DeadlineExceeded implements PropertyResult
{
    public function __construct(
        public DeadlineExceededException $exception,
    ) {}

    #[\Override]
    public function failure(): \Throwable
    {
        return $this->exception;
    }
}
