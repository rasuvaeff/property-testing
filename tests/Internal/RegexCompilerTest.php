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
        yield 'plus' => ['xa+y'];
    }

    #[DataProvider('deterministicPatterns')]
    public function deterministicPatternGeneratesExactString(string $pattern, string $expected): void
    {
        foreach (Gen::sample(RegexCompiler::compile($pattern), 5, 9) as $value) {
            Assert::same($value, $expected);
        }
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function deterministicPatterns(): iterable
    {
        yield 'plain literals' => ['abc', 'abc'];
        yield 'escaped meta' => ['a\\.b\\*c', 'a.b*c'];
        yield 'escaped brace' => ['a\\{b', 'a{b'];
        yield 'escaped pipe' => ['a\\|b', 'a|b'];
        yield 'capturing group' => ['(ab)c', 'abc'];
        yield 'non-capturing group' => ['(?:xy)z', 'xyz'];
        yield 'escaped tab/newline' => ['\\t\\n', "\t\n"];
        yield 'exact count' => ['a{3}', 'aaa'];
        yield 'zero count' => ['xa{0}y', 'xy'];
        yield 'range count both equal' => ['a{2,2}', 'aa'];
        yield 'nested groups' => ['((a)(b))', 'ab'];
        yield 'single-member class' => ['[q]', 'q'];
        yield 'single-char range' => ['[a-a]', 'a'];
        yield 'dash literal only' => ['[-]', '-'];
        yield 'dot inside class is literal' => ['[.]', '.'];
        yield 'group then literal' => ['(?:ab)cd', 'abcd'];
        yield 'literal then group' => ['xy(?:ab)', 'xyab'];
        yield 'empty group' => ['a()b', 'ab'];
    }

    public function alternationReachesEveryBranch(): void
    {
        $values = Gen::sample(RegexCompiler::compile('a|b'), 60, 4);

        Assert::true(in_array('a', $values, true));
        Assert::true(in_array('b', $values, true));
    }

    public function dotProducesVariousCharacters(): void
    {
        $values = Gen::sample(RegexCompiler::compile('.'), 60, 4);

        // `.` is any printable character, not the literal dot.
        Assert::true($this->anyMatches($values, '/[^.]/'));
    }

    public function digitClassCoversBothBounds(): void
    {
        $values = Gen::sample(RegexCompiler::compile('[\\d]'), 200, 4);

        Assert::true(in_array('0', $values, true));
        Assert::true(in_array('9', $values, true));
    }

    public function starCanProduceEmptyAndRepeated(): void
    {
        $values = Gen::sample(RegexCompiler::compile('a*', 6), 80, 4);
        $lengths = array_map(static fn(mixed $v): int => is_string($v) ? strlen($v) : -1, $values);

        // The lower bound is 0 (empty possible) and it repeats up to the cap.
        Assert::true(in_array(0, $lengths, true));
        Assert::true(max($lengths) >= 2);
        Assert::true(max($lengths) <= 6);
    }

    #[DataProvider('classPatterns')]
    public function characterClassGeneratesOnlyItsMembers(string $pattern, string $allowedRegex): void
    {
        foreach (Gen::sample(RegexCompiler::compile($pattern), 50, 4) as $value) {
            Assert::true(is_string($value) && preg_match('/^' . $allowedRegex . '$/', $value) === 1, (string) var_export($value, true));
        }
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function classPatterns(): iterable
    {
        yield 'digit shorthand' => ['\\d', '[0-9]'];
        yield 'word shorthand' => ['\\w', '[A-Za-z0-9_]'];
        yield 'space shorthand' => ['\\s', '\\s'];
        yield 'negated digit' => ['\\D', '[^0-9]'];
        yield 'explicit range' => ['[a-c]', '[abc]'];
        yield 'negated class' => ['[^0-9]', '[^0-9]'];
        yield 'class with shorthand' => ['[\\d_]', '[0-9_]'];
    }

    #[DataProvider('shorthandPatterns')]
    public function shorthandAtomAndClassMatch(string $pattern, string $allowedRegex): void
    {
        foreach (Gen::sample(RegexCompiler::compile($pattern), 60, 4) as $value) {
            Assert::true(is_string($value) && preg_match('/^' . $allowedRegex . '$/', $value) === 1, (string) var_export($value, true));
        }
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function shorthandPatterns(): iterable
    {
        // Each shorthand, both as an atom and inside a class, pins its own match arm.
        yield '\\d atom' => ['\\d', '[0-9]'];
        yield '\\w atom' => ['\\w', '[A-Za-z0-9_]'];
        yield '\\s atom' => ['\\s', '\\s'];
        yield '\\D atom' => ['\\D', '[^0-9]'];
        yield '\\W atom' => ['\\W', '[^A-Za-z0-9_]'];
        yield '\\S atom' => ['\\S', '\\S'];
        yield '\\d class' => ['[\\d]', '[0-9]'];
        yield '\\w class' => ['[\\w]', '[A-Za-z0-9_]'];
        yield '\\s class' => ['[\\s]', '\\s'];
        yield '\\D class' => ['[\\D]', '[^0-9]'];
        yield '\\W class' => ['[\\W]', '[^A-Za-z0-9_]'];
        yield '\\S class' => ['[\\S]', '\\S'];
    }

    #[DataProvider('controlEscapePatterns')]
    public function controlEscapesAreExact(string $pattern, string $expected): void
    {
        foreach (Gen::sample(RegexCompiler::compile($pattern), 5, 4) as $value) {
            Assert::same($value, $expected);
        }
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function controlEscapePatterns(): iterable
    {
        yield 'tab atom' => ['\\t', "\t"];
        yield 'newline atom' => ['\\n', "\n"];
        yield 'carriage return atom' => ['\\r', "\r"];
        yield 'tab in class' => ['[\\t]', "\t"];
        yield 'newline in class' => ['[\\n]', "\n"];
        yield 'carriage return in class' => ['[\\r]', "\r"];
    }

    public function wordShorthandCoversEveryCategory(): void
    {
        $values = Gen::sample(RegexCompiler::compile('\\w'), 400, 4);

        // Every character category the builder concatenates must be reachable —
        // dropping a range/item from `word()` would remove one of these.
        Assert::true($this->anyMatches($values, '/[a-z]/'));
        Assert::true($this->anyMatches($values, '/[A-Z]/'));
        Assert::true($this->anyMatches($values, '/[0-9]/'));
        Assert::true(in_array('_', $values, true));
    }

    public function whitespaceShorthandCoversEveryCharacter(): void
    {
        $values = Gen::sample(RegexCompiler::compile('\\s'), 400, 4);

        foreach ([' ', "\t", "\n", "\r"] as $whitespace) {
            Assert::true(in_array($whitespace, $values, true));
        }
    }

    public function digitShorthandCoversBothBounds(): void
    {
        $values = Gen::sample(RegexCompiler::compile('\\d'), 200, 4);

        Assert::true(in_array('0', $values, true));
        Assert::true(in_array('9', $values, true));
    }

    #[DataProvider('multiCharShorthands')]
    public function shorthandProducesMultipleDistinctCharacters(string $pattern): void
    {
        $values = array_filter(Gen::sample(RegexCompiler::compile($pattern), 120, 4), is_string(...));
        $distinct = array_unique($values);

        // A dropped shorthand arm falls back to a single literal character, so
        // "at least two distinct characters" pins every shorthand's real set —
        // including the negated ones whose literal fallback would still match.
        Assert::true(count($distinct) >= 2);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function multiCharShorthands(): iterable
    {
        foreach (['\\d', '\\w', '\\s', '\\D', '\\W', '\\S'] as $shorthand) {
            yield $shorthand . ' atom' => [$shorthand];
            yield $shorthand . ' class' => ['[' . $shorthand . ']'];
        }
    }

    /**
     * @param list<mixed> $values
     */
    private function anyMatches(array $values, string $regex): bool
    {
        foreach ($values as $value) {
            if (is_string($value) && preg_match($regex, $value) === 1) {
                return true;
            }
        }

        return false;
    }

    public function exactCountQuantifierProducesExactLength(): void
    {
        foreach (Gen::sample(RegexCompiler::compile('a{3}'), 10, 4) as $value) {
            Assert::same($value, 'aaa');
        }
    }

    public function optionalQuantifierProducesZeroOrOne(): void
    {
        $values = Gen::sample(RegexCompiler::compile('a?'), 40, 4);

        foreach ($values as $value) {
            Assert::true($value === '' || $value === 'a');
        }

        // Both the empty and the single-character case must be reachable.
        Assert::true(in_array('', $values, true));
        Assert::true(in_array('a', $values, true));
    }

    public function multibyteLiteralsAreGeneratedIntact(): void
    {
        foreach (Gen::sample(RegexCompiler::compile('café'), 5, 4) as $value) {
            Assert::same($value, 'café');
        }
    }

    public function maxRepeatOfOneAllowsASingleRepetition(): void
    {
        foreach (Gen::sample(RegexCompiler::compile('a+', 1), 10, 4) as $value) {
            Assert::same($value, 'a');
        }
    }

    #[DataProvider('errorMessagePatterns')]
    public function errorMessageNamesTheConstruct(string $pattern, string $needle): void
    {
        try {
            RegexCompiler::compile($pattern);
        } catch (\InvalidArgumentException $exception) {
            Assert::string($exception->getMessage())->contains($needle);

            return;
        }

        Assert::fail(sprintf('Expected /%s/ to be rejected', $pattern));
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function errorMessagePatterns(): iterable
    {
        yield 'backreference' => ['(a)\\1', 'backreference'];
        yield 'assertion escape' => ['\\bword', 'assertion'];
        yield 'group modifier' => ['(?=a)', 'group modifier'];
        yield 'anchor in middle' => ['a^b', 'anchor'];
        yield 'nothing to repeat' => ['*a', 'nothing to repeat'];
        yield 'unterminated group' => ['(ab', 'Unterminated regex group'];
        yield 'unterminated class' => ['[ab', 'Unterminated regex character class'];
        yield 'reversed quantifier' => ['a{5,2}', 'max < min'];
        yield 'reversed range' => ['[z-a]', 'out of order'];
        yield 'trailing backslash' => ['ab\\', 'Trailing backslash'];
        yield 'malformed quantifier' => ['a{2,x}', 'Malformed regex quantifier'];
        yield 'no comma or brace' => ['a{2x}', 'expected "," or "}"'];
        yield 'junk after upper bound' => ['a{2,3x}', 'expected "}"'];
        yield 'question nothing to repeat' => ['?x', 'nothing to repeat'];
        yield 'brace nothing to repeat' => ['{x', 'nothing to repeat'];
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

    public function maxRepeatMustBePositive(): void
    {
        try {
            RegexCompiler::compile('a+', 0);
        } catch (\InvalidArgumentException $exception) {
            Assert::string($exception->getMessage())->contains('maxRepeat');

            return;
        }

        Assert::fail('Expected maxRepeat=0 to be rejected');
    }
}
