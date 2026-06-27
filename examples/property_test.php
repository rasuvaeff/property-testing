<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Rasuvaeff\PropertyTesting\Gen;
use Rasuvaeff\PropertyTesting\Property;
use Testo\Assert;
use Testo\Test;

/**
 * Canonical #[Property] usage — the way you actually write property tests with
 * this package. The class below is a normal Testo test case; run it through the
 * Testo runner:
 *
 *   docker run --rm -v "$PWD":/app -w /app composer:2 vendor/bin/testo
 *
 * Executing this file directly with `php` only defines the test class and prints
 * the hint at the bottom — the property assertions run under Testo, which feeds
 * each parameter from the matching generators method, runs it `runs` times, and
 * shrinks the first failing input to a minimal counterexample.
 */
#[Test]
final class ListReversalProperties
{
    /**
     * Reversing a list twice yields the original list, for any list of ints.
     */
    #[Property(runs: 200)]
    public function reversingTwiceRestoresTheList(array $xs): void
    {
        Assert::same(array_reverse(array_reverse($xs)), $xs);
    }

    /** @return array<string, \Rasuvaeff\PropertyTesting\ArbitraryInterface> */
    private function reversingTwiceRestoresTheListGenerators(): array
    {
        return ['xs' => Gen::arrayOf(Gen::intBetween(-100, 100))];
    }

    /**
     * A fixed seed makes a failing run reproducible: pass the seed reported in
     * the failure message back through the attribute to replay the exact inputs.
     */
    #[Property(runs: 200, seed: 1234567, generators: 'sumGenerators')]
    public function sumOfNonNegativesIsNonNegative(int $a, int $b): void
    {
        Assert::true($a + $b >= 0);
    }

    /** @return array<string, \Rasuvaeff\PropertyTesting\ArbitraryInterface> */
    private function sumGenerators(): array
    {
        return [
            'a' => Gen::intBetween(0, 1_000_000),
            'b' => Gen::intBetween(0, 1_000_000),
        ];
    }
}

echo 'Defined ' . ListReversalProperties::class . " — run the properties with: vendor/bin/testo\n";
