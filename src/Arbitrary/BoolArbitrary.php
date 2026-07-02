<?php

declare(strict_types=1);

namespace Rasuvaeff\PropertyTesting\Arbitrary;

use Rasuvaeff\PropertyTesting\ArbitraryInterface;
use Rasuvaeff\PropertyTesting\Random;
use Rasuvaeff\PropertyTesting\Shrinkable;

/**
 * Generates booleans. false is the "smaller" boolean: true shrinks to false,
 * false is terminal.
 *
 * @api
 */
final readonly class BoolArbitrary implements ArbitraryInterface
{
    #[\Override]
    public function generate(Random $random): Shrinkable
    {
        if ($random->int(0, 1) === 1) {
            return Shrinkable::of(true, static fn(): array => [Shrinkable::leaf(false)]);
        }

        return Shrinkable::leaf(false);
    }
}
