<?php

declare(strict_types=1);

namespace Rasuvaeff\PropertyTesting\Tests\Internal;

use Rasuvaeff\PropertyTesting\ArbitraryInterface;
use Rasuvaeff\PropertyTesting\Gen;
use Rasuvaeff\PropertyTesting\Property;

/**
 * Fixtures for {@see \Rasuvaeff\PropertyTesting\Tests\Internal\PropertyInterceptorTest}.
 * Not covered by coverage: they exist only to carry #[Property] attributes and
 * generators methods discoverable via reflection.
 */
final class PassingStub
{
    #[Property(runs: 5, seed: 1, generators: 'provide')]
    public function check(int $x): void {}

    /** @return array<string, ArbitraryInterface> */
    public static function provide(): array
    {
        return ['x' => Gen::intBetween(1, 10)];
    }
}

final class ConventionStub
{
    #[Property(runs: 3, seed: 1)]
    public function check(int $x): void {}

    /** @return array<string, ArbitraryInterface> */
    public static function checkGenerators(): array
    {
        return ['x' => Gen::intBetween(1, 10)];
    }
}

final class FalsifyingStub
{
    #[Property(runs: 1, seed: 1, generators: 'provide')]
    public function check(int $x): void {}

    /** @return array<string, ArbitraryInterface> */
    public static function provide(): array
    {
        return ['x' => Gen::intBetween(51, 100)];
    }
}

final class MultiParamFalsifyingStub
{
    #[Property(runs: 1, seed: 1, generators: 'provide')]
    public function check(int $a, int $b): void {}

    /** @return array<string, ArbitraryInterface> */
    public static function provide(): array
    {
        return ['a' => Gen::intBetween(0, 10), 'b' => Gen::intBetween(51, 100)];
    }
}

final class PlainStub
{
    public function check(int $x): void {}
}

final class MissingParameterGeneratorStub
{
    #[Property(runs: 1, seed: 1, generators: 'provide')]
    public function check(int $x, int $y): void {}

    /** @return array<string, ArbitraryInterface> */
    public static function provide(): array
    {
        return ['x' => Gen::int()];
    }
}

final class MissingGeneratorMethodStub
{
    #[Property(runs: 1, seed: 1, generators: 'doesNotExist')]
    public function check(int $x): void {}
}

final class MaxShrinksCapStub
{
    #[Property(runs: 1, seed: 1, generators: 'provide', maxShrinks: 1)]
    public function check(int $a, int $b): void {}

    /** @return array<string, ArbitraryInterface> */
    public static function provide(): array
    {
        return ['a' => Gen::intBetween(10, 100), 'b' => Gen::intBetween(10, 100)];
    }
}

final class MaxShrinksDisabledStub
{
    #[Property(runs: 1, seed: 1, generators: 'provide', maxShrinks: 0)]
    public function check(int $a): void {}

    /** @return array<string, ArbitraryInterface> */
    public static function provide(): array
    {
        return ['a' => Gen::intBetween(10, 100)];
    }
}

final class NoSeedFalsifyingStub
{
    #[Property(runs: 1, generators: 'provide')]
    public function check(int $x): void {}

    /** @return array<string, ArbitraryInterface> */
    public static function provide(): array
    {
        return ['x' => Gen::intBetween(51, 100)];
    }
}

final class MappedFalsifyingStub
{
    #[Property(runs: 50, seed: 1, generators: 'provide')]
    public function check(int $x): void {}

    /** @return array<string, ArbitraryInterface> */
    public static function provide(): array
    {
        // Doubled ints: with integrated shrinking the mapped value shrinks
        // through the source int's tree (pre-2.0, map() did not shrink at all).
        return ['x' => Gen::map(Gen::intBetween(0, 100), static fn(int $n): int => $n * 2)];
    }
}

final class FlatMapFalsifyingStub
{
    #[Property(runs: 50, seed: 1, generators: 'provide')]
    public function check(int $x): void {}

    /** @return array<string, ArbitraryInterface> */
    public static function provide(): array
    {
        // Dependent generator: the value's domain [0, n] depends on the source n.
        return ['x' => Gen::flatMap(
            Gen::intBetween(1, 10),
            static fn(int $n): ArbitraryInterface => Gen::intBetween(0, $n),
        )];
    }
}
