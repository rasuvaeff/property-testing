<?php

declare(strict_types=1);

namespace Rasuvaeff\PropertyTesting\Tests\Arbitrary;

use Rasuvaeff\PropertyTesting\Arbitrary\StringArbitrary;
use Rasuvaeff\PropertyTesting\Random;
use Testo\Assert;
use Testo\Assert\ExpectException;
use Testo\Codecov\Covers;
use Testo\Test;

#[Test]
#[Covers(StringArbitrary::class)]
final class StringArbitraryTest
{
    public function generateStaysWithinLengthRange(): void
    {
        $arbitrary = new StringArbitrary(2, 8, unicode: false);
        $random = new Random(1);

        for ($i = 0; $i < 200; ++$i) {
            $length = strlen($arbitrary->generate($random));

            Assert::true($length >= 2 && $length <= 8);
        }
    }

    public function shrinkTriesEmptyStringFirstThenHalves(): void
    {
        $candidates = iterator_to_array((new StringArbitrary())->shrink('hello world'), false);

        Assert::same($candidates[0], '');
        Assert::true(in_array('h', $candidates, true));
    }

    public function shrinkReducesCharactersTowardLowercaseA(): void
    {
        // After the length candidates, each character is driven toward 'a' in place.
        $candidates = iterator_to_array((new StringArbitrary())->shrink('hi'), false);

        Assert::true(in_array('ai', $candidates, true));
        Assert::true(in_array('ha', $candidates, true));
    }

    public function shrinkNeverEscapesBelowMinimumLength(): void
    {
        // stringOf(5, 10)-style generator must never shrink to '' (out of domain).
        $candidates = iterator_to_array((new StringArbitrary(5, 10))->shrink('abcdefgh'), false);

        Assert::false(in_array('', $candidates, true));

        foreach ($candidates as $candidate) {
            Assert::true(mb_strlen((string) $candidate) >= 5);
        }
    }

    public function shrinkOfEmptyStringYieldsNothing(): void
    {
        Assert::same(iterator_to_array((new StringArbitrary())->shrink('')), []);
    }

    public function unicodeGenerationProducesValidUtf8(): void
    {
        $arbitrary = new StringArbitrary(1, 1, unicode: true);

        for ($seed = 1; $seed <= 50; ++$seed) {
            $value = $arbitrary->generate(new Random($seed));

            Assert::same(mb_check_encoding($value, 'UTF-8'), true);
            // A single requested character must yield exactly one codepoint (never
            // the empty fallback), and it must be valid UTF-8.
            Assert::same(mb_strlen($value, 'UTF-8'), 1);
        }
    }

    public function generateReachesBothExactLengthBounds(): void
    {
        $arbitrary = new StringArbitrary(1, 4, unicode: false);
        $random = new Random(1);
        $min = PHP_INT_MAX;
        $max = 0;

        for ($i = 0; $i < 200; ++$i) {
            $length = strlen($arbitrary->generate($random));
            $min = min($min, $length);
            $max = max($max, $length);
        }

        Assert::same($min, 1);
        Assert::same($max, 4);
    }

    public function shrinkLengthPhaseYieldsEmptyThenExactHalves(): void
    {
        $candidates = iterator_to_array((new StringArbitrary())->shrink('abcdefgh'), false);

        Assert::same($candidates[0], '');
        Assert::same($candidates[1], 'abcd');
        Assert::same($candidates[2], 'ab');
        Assert::same($candidates[3], 'a');
    }

    public function shrinkHalvesAndDrivesCharactersByCharacterNotByte(): void
    {
        // 'αβγδ' is four 2-byte codepoints; both the length halving and the
        // character-to-'a' phase must operate per character, never per byte.
        $candidates = iterator_to_array((new StringArbitrary())->shrink('αβγδ'), false);

        Assert::same($candidates[0], '');
        Assert::same($candidates[1], 'αβ');
        Assert::same($candidates[2], 'α');
        Assert::same(in_array('aβγδ', $candidates, true), true);
    }

    public function shrinkCharacterPhaseSkipsOnlyTheCharactersAlreadyA(): void
    {
        // The middle 'a' is skipped, but the loop continues past it: the trailing
        // 'b' must still be driven to 'a'. ('bab' -> '', 'b', 'aab', 'baa'.)
        $candidates = iterator_to_array((new StringArbitrary())->shrink('bab'), false);

        Assert::same($candidates, ['', 'b', 'aab', 'baa']);
    }

    public function shrinkKeepsTheMinimumLengthCandidate(): void
    {
        // With minLength 2 the length-floor candidate (exactly 2 chars) is produced.
        $candidates = iterator_to_array((new StringArbitrary(2, 100))->shrink('abcdefgh'), false);
        $lengthTwo = array_filter($candidates, static fn(string $c): bool => mb_strlen($c, 'UTF-8') === 2);

        Assert::same(in_array('ab', $candidates, true), true);
        Assert::true($lengthTwo !== []);
    }

    #[ExpectException(\InvalidArgumentException::class)]
    public function rejectsNegativeMinimumLength(): void
    {
        new StringArbitrary(-1, 5);
    }

    #[ExpectException(\InvalidArgumentException::class)]
    public function rejectsZeroMaximumLength(): void
    {
        new StringArbitrary(0, 0);
    }

    #[ExpectException(\InvalidArgumentException::class)]
    public function rejectsInvertedLength(): void
    {
        new StringArbitrary(10, 2);
    }
}
