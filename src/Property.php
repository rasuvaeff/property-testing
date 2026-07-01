<?php

declare(strict_types=1);

namespace Rasuvaeff\PropertyTesting;

use Rasuvaeff\PropertyTesting\Internal\PropertyInterceptor;
use Testo\Pipeline\Attribute\FallbackInterceptor;
use Testo\Pipeline\Attribute\Interceptable;

/**
 * Marks a test method as a property: the {@see PropertyInterceptor} takes over,
 * generating random arguments from a generators method and running the property
 * {@see $runs} times.
 *
 * Attribute arguments in PHP must be constant expressions, so the generators
 * cannot be passed inline. Instead name a method (on the same test case) that
 * returns `array<string, ArbitraryInterface>`, keyed by parameter name. When
 * {@see $generators} is null the runner falls back to a method named
 * `<testMethod>Generators`.
 *
 * @api
 */
#[\Attribute(\Attribute::TARGET_METHOD | \Attribute::TARGET_FUNCTION)]
#[FallbackInterceptor(PropertyInterceptor::class)]
final readonly class Property implements Interceptable
{
    /**
     * @param int $runs Number of random inputs to try.
     * @param ?int $seed Fixed seed for reproducibility. Omit to let the runner pick a random one
     *        (the failing seed is reported by {@see PropertyViolationException}).
     * @param ?string $generators Method name returning array<string, ArbitraryInterface>.
     *        Defaults to `<testMethod>Generators`.
     * @param ?int $maxShrinks Cap on the number of accepted shrink steps. Null (default) means
     *        no cap. 0 disables shrinking, reporting the original counterexample unchanged.
     */
    public function __construct(
        public int $runs = 100,
        public ?int $seed = null,
        public ?string $generators = null,
        public ?int $maxShrinks = null,
    ) {
        if ($runs < 1) {
            throw new \InvalidArgumentException('Runs must be greater than or equal to 1');
        }
        if ($maxShrinks !== null && $maxShrinks < 0) {
            throw new \InvalidArgumentException('Max shrinks must be greater than or equal to 0');
        }
    }
}
