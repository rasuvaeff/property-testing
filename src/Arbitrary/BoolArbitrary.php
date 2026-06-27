<?php

declare(strict_types=1);

namespace Rasuvaeff\PropertyTesting\Arbitrary;

use Rasuvaeff\PropertyTesting\ArbitraryInterface;
use Rasuvaeff\PropertyTesting\Random;

/**
 * Generates booleans. Booleans have no meaningful shrink.
 *
 * @api
 */
final readonly class BoolArbitrary implements ArbitraryInterface
{
    #[\Override]
    public function generate(Random $random): bool
    {
        return $random->int(0, 1) === 1;
    }

    #[\Override]
    public function shrink(mixed $value): iterable
    {
        if (!is_bool($value)) {
            return;
        }

        // false is the "smaller" boolean.
        if ($value) {
            yield false;
        }
    }
}
