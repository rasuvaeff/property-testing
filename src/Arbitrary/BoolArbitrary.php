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
 * @implements ArbitraryInterface<bool>
 * @api
 */
final readonly class BoolArbitrary implements ArbitraryInterface
{
    #[\Override]
    public function generate(Random $random): Shrinkable
    {
        /** @var bool $value */
        $value = $random->int(0, 1) === 1;

        /** @var list<Shrinkable<bool>> $shrinks */
        $shrinks = $value ? [Shrinkable::leaf(false)] : [];

        return Shrinkable::of($value, static fn(): array => $shrinks);
    }
}
