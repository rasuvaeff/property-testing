<?php

declare(strict_types=1);

namespace Rasuvaeff\PropertyTesting\Event;

/**
 * The property is about to run: examples first, then corpus replay, then the
 * random phase.
 *
 * @internal
 */
final readonly class PropertyStarted implements PropertyEvent
{
    public function __construct(
        public string $propertyId,
        public int $seed,
        public int $runs,
    ) {}
}
