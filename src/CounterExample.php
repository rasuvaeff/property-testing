<?php

declare(strict_types=1);

namespace Rasuvaeff\PropertyTesting;

/**
 * Minimal failing input for a property, captured at falsification time.
 *
 * Carries both the original (randomly generated) counterexample and the
 * shrunk (minimised) one, plus the seed needed to reproduce the run.
 *
 * @api
 */
final readonly class CounterExample
{
    /**
     * @param int $seed Seed of the run that first failed (pass it to {@see Property} to reproduce).
     * @param int $runsBeforeFailure Number of successful (non-discarded) runs before the failure.
     * @param array<string, mixed> $originalArguments Randomly generated arguments that first failed.
     * @param array<string, mixed> $shrunkArguments Minimised arguments that still fail.
     * @param int $shrinkSteps Number of accepted shrink steps between the original and the minimised arguments.
     * @param ?\Throwable $failure The assertion or exception reported by the failing run.
     * @param int $skips Number of runs discarded via {@see Assume::that()} before the failure.
     */
    public function __construct(
        public int $seed,
        public int $runsBeforeFailure,
        public array $originalArguments,
        public array $shrunkArguments,
        public int $shrinkSteps = 0,
        public ?\Throwable $failure = null,
        public int $skips = 0,
    ) {}
}
