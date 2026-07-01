<?php

declare(strict_types=1);

namespace Rasuvaeff\PropertyTesting\Tests;

use Rasuvaeff\PropertyTesting\ArbitraryInterface;
use Rasuvaeff\PropertyTesting\Gen;
use Rasuvaeff\PropertyTesting\Property;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Test;

#[Test]
#[Covers(Gen::class)]
final class GenPropertyTest
{
    #[Property(runs: 50, seed: 123)]
    public function intBetweenGeneratesValuesInsideTheConfiguredRange(int $value): void
    {
        Assert::true($value >= -100 && $value <= 100);
    }

    /** @return array<string, ArbitraryInterface> */
    private function intBetweenGeneratesValuesInsideTheConfiguredRangeGenerators(): array
    {
        return ['value' => Gen::intBetween(-100, 100)];
    }

    #[Property(runs: 50, seed: 456)]
    public function stringOfGeneratesStringsInsideTheConfiguredLengthRange(string $value): void
    {
        $length = mb_strlen($value, 'UTF-8');

        Assert::true($length >= 2 && $length <= 8);
    }

    /** @return array<string, ArbitraryInterface> */
    private function stringOfGeneratesStringsInsideTheConfiguredLengthRangeGenerators(): array
    {
        return ['value' => Gen::stringOf(2, 8)];
    }

    #[Property(runs: 50, seed: 789)]
    public function arrayOfGeneratesListsWhoseElementsComeFromTheInnerGenerator(array $values): void
    {
        foreach ($values as $value) {
            Assert::true(is_int($value));
            Assert::true($value >= 1 && $value <= 3);
        }
    }

    /** @return array<string, ArbitraryInterface> */
    private function arrayOfGeneratesListsWhoseElementsComeFromTheInnerGeneratorGenerators(): array
    {
        return ['values' => Gen::arrayOf(Gen::intBetween(1, 3))];
    }

    #[Property(runs: 50, seed: 321)]
    public function dictOfGeneratesMapsWithKeysAndValuesFromTheirGenerators(array $map): void
    {
        foreach ($map as $key => $value) {
            Assert::true(is_string($key));
            Assert::true(is_int($value) && $value >= 1 && $value <= 3);
        }
    }

    /** @return array<string, ArbitraryInterface> */
    private function dictOfGeneratesMapsWithKeysAndValuesFromTheirGeneratorsGenerators(): array
    {
        return ['map' => Gen::dictOf(Gen::stringOf(1, 5), Gen::intBetween(1, 3))];
    }

    #[Property(runs: 50, seed: 654)]
    public function recordGeneratesEveryFieldWithinItsDomain(array $record): void
    {
        Assert::true(is_int($record['age']) && $record['age'] >= 0 && $record['age'] <= 120);
        Assert::true(is_bool($record['active']));
        Assert::same(array_keys($record), ['age', 'active']);
    }

    /** @return array<string, ArbitraryInterface> */
    private function recordGeneratesEveryFieldWithinItsDomainGenerators(): array
    {
        return ['record' => Gen::record([
            'age' => Gen::intBetween(0, 120),
            'active' => Gen::bool(),
        ])];
    }
}
