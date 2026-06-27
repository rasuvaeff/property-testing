<?php

declare(strict_types=1);

namespace Rasuvaeff\PropertyTesting;

use RuntimeException;

/**
 * Reported when a property is falsified.
 *
 * Carries the {@see CounterExample} so reporters (and tests of this package)
 * can inspect the seed and the shrunk arguments. Its message renders a
 * human-readable summary.
 *
 * @api
 */
final class PropertyViolationException extends RuntimeException
{
    public function __construct(private readonly CounterExample $counterExample)
    {
        parent::__construct($this->render($counterExample), previous: $counterExample->failure);
    }

    public function getCounterExample(): CounterExample
    {
        return $this->counterExample;
    }

    private function render(CounterExample $c): string
    {
        $original = $this->format($c->originalArguments);
        $shrunk = $this->format($c->shrunkArguments);

        $message = sprintf(
            "Property falsified after %d successful run(s); seed=%d\n  Original: %s\n  Shrunk:   %s (%d shrink step(s))",
            $c->runsBeforeFailure,
            $c->seed,
            $original,
            $shrunk,
            $c->shrinkSteps,
        );

        if ($c->failure !== null) {
            $message .= sprintf("\n  Failure:  %s", $c->failure->getMessage());
        }

        return $message;
    }

    /**
     * @param array<string, mixed> $arguments
     */
    private function format(array $arguments): string
    {
        $pairs = array_map(
            static fn(mixed $value, mixed $name): string => $name . '=' . self::scalar($value),
            $arguments,
            array_keys($arguments),
        );

        return implode(', ', $pairs);
    }

    private static function scalar(mixed $value): string
    {
        return match (true) {
            is_array($value) => '[' . count($value) . ' element(s)]',
            is_string($value) => '"' . $value . '"',
            is_bool($value) => $value ? 'true' : 'false',
            is_null($value) => 'null',
            is_scalar($value) => (string) $value,
            default => get_debug_type($value),
        };
    }
}
