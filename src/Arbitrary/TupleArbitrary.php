<?php

declare(strict_types=1);

namespace Rasuvaeff\PropertyTesting\Arbitrary;

use Rasuvaeff\PropertyTesting\ArbitraryInterface;
use Rasuvaeff\PropertyTesting\Random;

/**
 * Fixed-arity tuple: produces a list with one value per element arbitrary, in
 * order. Useful for generating several correlated parameters as a single value
 * (the property receives the tuple as one array argument and destructures it).
 *
 * Shrinking keeps the arity fixed and shrinks one position at a time through
 * that position's own arbitrary, so each component shrinks within its domain.
 *
 * @api
 */
final readonly class TupleArbitrary implements ArbitraryInterface
{
    /** @var non-empty-list<ArbitraryInterface> */
    private array $elements;

    public function __construct(ArbitraryInterface ...$elements)
    {
        if ($elements === []) {
            throw new \InvalidArgumentException('Tuple requires at least one element arbitrary');
        }

        // Named variadic arguments arrive string-keyed; re-index to a list so
        // generate()/shrink() address each element by its positional index.
        $this->elements = array_values($elements);
    }

    #[\Override]
    public function generate(Random $random): array
    {
        return array_map(
            static fn(ArbitraryInterface $element): mixed => $element->generate($random),
            $this->elements,
        );
    }

    #[\Override]
    public function shrink(mixed $value): iterable
    {
        if (!is_array($value) || count($value) !== count($this->elements)) {
            return;
        }

        // Arity is fixed: there is no length phase. Re-index to a list so each
        // position maps to the element arbitrary at the same index, then shrink
        // one position at a time, keeping the others as-is.
        $values = array_values($value);

        foreach ($this->elements as $index => $element) {
            /** @var mixed $smaller */
            foreach ($element->shrink($values[$index]) as $smaller) {
                yield array_replace($values, [$index => $smaller]);
            }
        }
    }
}
