<?php

declare(strict_types=1);

namespace Rasuvaeff\PropertyTesting\Arbitrary;

use Rasuvaeff\PropertyTesting\ArbitraryInterface;
use Rasuvaeff\PropertyTesting\Random;
use Rasuvaeff\PropertyTesting\Shrinkable;

/**
 * Wraps another arbitrary and additionally yields `null` with roughly even odds.
 *
 * Shrinking prefers `null` over descending into the inner value's tree.
 *
 * @api
 */
final readonly class NullableArbitrary implements ArbitraryInterface
{
    public function __construct(
        private ArbitraryInterface $inner,
    ) {}

    #[\Override]
    public function generate(Random $random): Shrinkable
    {
        return $random->int(0, 1) === 1
            ? Shrinkable::leaf(null)
            : $this->wrap($this->inner->generate($random));
    }

    private function wrap(Shrinkable $inner): Shrinkable
    {
        return Shrinkable::of($inner->value, function () use ($inner): \Generator {
            yield Shrinkable::leaf(null);

            foreach ($inner->shrinks() as $smaller) {
                yield $this->wrap($smaller);
            }
        });
    }
}
