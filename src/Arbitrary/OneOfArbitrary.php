<?php

declare(strict_types=1);

namespace Rasuvaeff\PropertyTesting\Arbitrary;

use Rasuvaeff\PropertyTesting\ArbitraryInterface;
use Rasuvaeff\PropertyTesting\Random;

/**
 * Picks a value uniformly at random from a fixed set.
 *
 * Values are used verbatim (they are not arbitraries), so shrinking is limited
 * to yielding each distinct candidate in turn. Use this for enumerations and
 * small tagged unions.
 *
 * @api
 */
final readonly class OneOfArbitrary implements ArbitraryInterface
{
    /** @var list<mixed> */
    private array $values;

    public function __construct(
        mixed ...$values,
    ) {
        if ($values === []) {
            throw new \InvalidArgumentException('OneOf requires at least one value');
        }

        /** @var list<mixed> $values */
        $this->values = $values;
    }

    #[\Override]
    public function generate(Random $random): mixed
    {
        return $this->values[$random->int(0, count($this->values) - 1)];
    }

    #[\Override]
    public function shrink(mixed $value): iterable
    {
        // Yield each distinct candidate that differs from the failing value;
        // the first one that still fails becomes a smaller counterexample.
        /** @var array<string, true> $seen */
        $seen = [];

        /** @var mixed $candidate */
        foreach ($this->values as $candidate) {
            if ($candidate === $value) {
                continue;
            }

            // Deduplicate identical candidates.
            $key = var_export($candidate, true);
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;

            yield $candidate;
        }
    }
}
