<?php

declare(strict_types=1);

namespace Rasuvaeff\PropertyTesting;

use Rasuvaeff\PropertyTesting\Event\PropertyEvent;

/**
 * Observer of a property run's lifecycle events.
 *
 * Listeners receive events in registration order, sequentially. A listener
 * exception is not swallowed: it aborts the property run as an infrastructure
 * failure. A listener observes — it can never change a property's outcome.
 *
 * @internal Stabilising for the engine split; becomes `@api` in
 *   property-testing-core 1.0 once the event set survives its consumer spikes.
 */
interface PropertyListener
{
    public function onEvent(PropertyEvent $event): void;
}
