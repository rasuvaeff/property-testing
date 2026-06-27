<?php

declare(strict_types=1);

namespace Rasuvaeff\PropertyTesting\Benchmarks;

use Rasuvaeff\PropertyTesting\Gen;
use Rasuvaeff\PropertyTesting\Random;
use Testo\Bench;

final class GeneratorBench
{
    #[Bench([], calls: 1_000, iterations: 5)]
    public static function intBetweenGenerate(): int
    {
        return Gen::intBetween(-1_000, 1_000)->generate(new Random(123));
    }

    #[Bench([], calls: 1_000, iterations: 5)]
    public static function stringAsciiGenerate(): string
    {
        return Gen::stringAscii()->generate(new Random(123));
    }

    #[Bench([], calls: 1_000, iterations: 5)]
    public static function arrayOfGenerate(): array
    {
        return Gen::arrayOf(Gen::intBetween(1, 10))->generate(new Random(123));
    }
}
