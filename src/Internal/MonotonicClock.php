<?php

declare(strict_types=1);

namespace Rasuvaeff\PropertyTesting\Internal;

/**
 * @internal
 */
final readonly class MonotonicClock implements Clock
{
    #[\Override]
    public function nanoseconds(): int
    {
        return hrtime(true);
    }
}
