<?php

declare(strict_types=1);

namespace Rasuvaeff\PropertyTesting;

use RuntimeException;

/**
 * Reported when an explicit example (a fixed input declared via the property's
 * `Examples` method) fails. Examples run before the random inputs and are not
 * shrunk — they are already the minimal case the developer pinned — so this
 * carries the example's index and arguments verbatim.
 *
 * @api
 */
final class ExampleViolationException extends RuntimeException
{
    /**
     * @param int $index Zero-based position of the failing example.
     * @param list<mixed> $arguments The example's positional arguments.
     * @param ?\Throwable $failure The assertion or exception the example raised.
     */
    public function __construct(
        private readonly int $index,
        private readonly array $arguments,
        ?\Throwable $failure = null,
    ) {
        $message = sprintf('Explicit example #%d failed: [%s]', $index, self::format($arguments));

        if ($failure instanceof \Throwable) {
            $message .= sprintf("\n  Failure:  %s", $failure->getMessage());
        }

        parent::__construct($message, previous: $failure);
    }

    public function getIndex(): int
    {
        return $this->index;
    }

    /**
     * @return list<mixed>
     */
    public function getArguments(): array
    {
        return $this->arguments;
    }

    /**
     * @param list<mixed> $arguments
     */
    private static function format(array $arguments): string
    {
        return implode(', ', array_map(
            static fn(mixed $value): string => match (true) {
                is_array($value) => '[' . count($value) . ' element(s)]',
                is_string($value) => '"' . $value . '"',
                is_bool($value) => $value ? 'true' : 'false',
                is_null($value) => 'null',
                is_scalar($value) => (string) $value,
                $value instanceof \Stringable => (string) $value,
                default => get_debug_type($value),
            },
            $arguments,
        ));
    }
}
