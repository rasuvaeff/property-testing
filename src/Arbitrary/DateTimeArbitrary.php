<?php

declare(strict_types=1);

namespace Rasuvaeff\PropertyTesting\Arbitrary;

use DateTimeImmutable;
use Rasuvaeff\PropertyTesting\ArbitraryInterface;
use Rasuvaeff\PropertyTesting\Random;

/**
 * Generates UTC {@see DateTimeImmutable} values with a Unix timestamp drawn
 * uniformly from an inclusive range, and shrinks toward the Unix epoch
 * (1970-01-01T00:00:00Z), clamped to the configured range.
 *
 * @api
 */
final readonly class DateTimeArbitrary implements ArbitraryInterface
{
    private const int DEFAULT_MAX_TIMESTAMP = 4_102_444_800; // 2100-01-01T00:00:00Z

    private int $minTimestamp;

    private int $maxTimestamp;

    public function __construct(?DateTimeImmutable $min = null, ?DateTimeImmutable $max = null)
    {
        $this->minTimestamp = $min?->getTimestamp() ?? 0;
        $this->maxTimestamp = $max?->getTimestamp() ?? self::DEFAULT_MAX_TIMESTAMP;

        if ($this->minTimestamp > $this->maxTimestamp) {
            throw new \InvalidArgumentException('Min must be less than or equal to max');
        }
    }

    #[\Override]
    public function generate(Random $random): DateTimeImmutable
    {
        return new DateTimeImmutable('@' . $random->int($this->minTimestamp, $this->maxTimestamp));
    }

    #[\Override]
    public function shrink(mixed $value): iterable
    {
        if (!$value instanceof DateTimeImmutable) {
            return;
        }

        // Shrink toward the epoch, clamped into the configured range (mirrors
        // IntArbitrary/FloatArbitrary: the target is the nearest in-range bound).
        $target = max($this->minTimestamp, min($this->maxTimestamp, 0));

        if ($value->getTimestamp() !== $target) {
            yield new DateTimeImmutable('@' . $target);
        }
    }
}
