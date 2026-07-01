<?php

declare(strict_types=1);

namespace Rasuvaeff\PropertyTesting\Arbitrary;

use Rasuvaeff\PropertyTesting\ArbitraryInterface;
use Rasuvaeff\PropertyTesting\Random;

/**
 * Fixed-shape associative array: produces a map with one value per named field,
 * each drawn from that field's arbitrary. Useful for generating DTO-shaped
 * payloads where every key is known up front (the property receives the record
 * as a single string-keyed array argument).
 *
 * Shrinking keeps the key set fixed and shrinks one field at a time through that
 * field's own arbitrary, so each value shrinks within its domain.
 *
 * @api
 */
final readonly class RecordArbitrary implements ArbitraryInterface
{
    /** @var non-empty-array<string, ArbitraryInterface> */
    private array $shape;

    /**
     * @param array<string, ArbitraryInterface> $shape Field name => arbitrary for that field.
     */
    public function __construct(array $shape)
    {
        if ($shape === []) {
            throw new \InvalidArgumentException('Record requires at least one field');
        }

        $this->shape = $shape;
    }

    /**
     * @return array<string, mixed>
     */
    #[\Override]
    public function generate(Random $random): array
    {
        // array_map over the shape preserves the field keys, so the record is
        // built without a per-key mixed assignment.
        return array_map(
            static fn(ArbitraryInterface $arbitrary): mixed => $arbitrary->generate($random),
            $this->shape,
        );
    }

    #[\Override]
    public function shrink(mixed $value): iterable
    {
        if (!is_array($value)) {
            return;
        }

        // The key set is fixed: there is no length phase. Shrink one field at a
        // time through its arbitrary, keeping the others as-is. Guard each key by
        // presence (not by count) so a same-size array with different keys does
        // not index a missing offset.
        foreach ($this->shape as $key => $arbitrary) {
            if (!array_key_exists($key, $value)) {
                continue;
            }

            /** @var mixed $smaller */
            foreach ($arbitrary->shrink($value[$key]) as $smaller) {
                yield array_replace($value, [$key => $smaller]);
            }
        }
    }
}
