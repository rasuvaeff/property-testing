<?php

declare(strict_types=1);

namespace Rasuvaeff\PropertyTesting\Arbitrary;

use Rasuvaeff\PropertyTesting\ArbitraryInterface;
use Rasuvaeff\PropertyTesting\Random;

/**
 * Always produces the same fixed value. Useful as a building block for composite
 * generators (e.g. a `record` field that is held constant) and for pinning one
 * parameter while others vary.
 *
 * There is nothing smaller than a constant, so it does not shrink.
 *
 * @api
 */
final readonly class ConstantArbitrary implements ArbitraryInterface
{
    public function __construct(
        private mixed $value,
    ) {}

    #[\Override]
    public function generate(Random $random): mixed
    {
        return $this->value;
    }

    #[\Override]
    public function shrink(mixed $value): iterable
    {
        yield from [];
    }
}
