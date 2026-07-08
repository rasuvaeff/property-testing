<?php

declare(strict_types=1);

namespace Rasuvaeff\PropertyTesting\Tests\Internal;

use Rasuvaeff\PropertyTesting\Gen;
use Rasuvaeff\PropertyTesting\Internal\RegexCompiler;
use Testo\Assert;
use Testo\Assert\ExpectException;
use Testo\Codecov\Covers;
use Testo\Data\DataProvider;
use Testo\Test;

#[Test]
#[Covers(RegexCompiler::class)]
final class RegexCompilerTest
{
    #[DataProvider('matchingPatterns')]
    public function everyGeneratedValueMatchesThePattern(string $pattern): void
    {
        $values = Gen::sample(RegexCompiler::compile($pattern), 40, 12345);

        foreach ($values as $value) {
            Assert::true(
                is_string($value) && preg_match('/^(?:' . $pattern . ')$/u', $value) === 1,
                sprintf('Value %s does not match /%s/', var_export($value, true), $pattern),
            );
        }
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function matchingPatterns(): iterable
    {
        yield 'literal' => ['abc'];
        yield 'digits class' => ['[0-9]+'];
        yield 'word' => ['\\w{3,6}'];
        yield 'alternation' => ['cat|dog|bird'];
        yield 'optional' => ['colou?r'];
        yield 'group repeat' => ['(ab)+'];
        yield 'non-capturing group' => ['(?:xy)*z'];
        yield 'negated class' => ['[^0-9]{2}'];
        yield 'dot star' => ['a.*b'];
        yield 'digit escape' => ['\\d\\d\\d'];
        yield 'exact count' => ['x{4}'];
        yield 'range count' => ['[a-f]{2,4}'];
        yield 'mixed' => ['(foo|bar)_[0-9]{1,3}'];
        yield 'anchored' => ['^[a-z]+$'];
        yield 'escaped meta' => ['a\\.b\\*c'];
        yield 'unbounded lower' => ['a{2,}'];
    }

    public function anchorsAtBoundariesAreStripped(): void
    {
        $values = Gen::sample(RegexCompiler::compile('^ab$'), 5, 1);

        foreach ($values as $value) {
            Assert::same($value, 'ab');
        }
    }

    public function escapedDollarStaysLiteral(): void
    {
        $values = Gen::sample(RegexCompiler::compile('a\\$'), 5, 1);

        foreach ($values as $value) {
            Assert::same($value, 'a$');
        }
    }

    public function quantifierBoundsAreRespected(): void
    {
        $values = Gen::sample(RegexCompiler::compile('a{2,4}'), 60, 7);

        foreach ($values as $value) {
            Assert::true(is_string($value) && strlen($value) >= 2 && strlen($value) <= 4);
        }
    }

    public function unboundedQuantifierIsCappedByMaxRepeat(): void
    {
        $values = Gen::sample(RegexCompiler::compile('a+', 3), 80, 7);

        foreach ($values as $value) {
            Assert::true(is_string($value) && strlen($value) >= 1 && strlen($value) <= 3);
        }
    }

    #[DataProvider('unsupportedPatterns')]
    #[ExpectException(\InvalidArgumentException::class)]
    public function rejectsUnsupportedConstructs(string $pattern): void
    {
        RegexCompiler::compile($pattern);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function unsupportedPatterns(): iterable
    {
        yield 'backreference' => ['(a)\\1'];
        yield 'lookahead' => ['a(?=b)'];
        yield 'lookbehind' => ['(?<=a)b'];
        yield 'named group' => ['(?<name>a)'];
        yield 'inline flag' => ['(?i)abc'];
        yield 'word boundary' => ['\\bword'];
        yield 'anchor in middle' => ['a^b'];
        yield 'dollar in middle' => ['a$b'];
        yield 'unterminated group' => ['(abc'];
        yield 'unterminated class' => ['[abc'];
        yield 'dangling star' => ['*abc'];
        yield 'dangling plus' => ['+'];
        yield 'nothing before quantifier' => ['(|*)'];
        yield 'malformed quantifier' => ['a{2,x}'];
        yield 'reversed quantifier' => ['a{5,2}'];
        yield 'trailing backslash' => ['abc\\'];
        yield 'reversed range' => ['[z-a]'];
    }

    #[ExpectException(\InvalidArgumentException::class)]
    public function maxRepeatMustBePositive(): void
    {
        RegexCompiler::compile('a+', 0);
    }
}
