<?php

declare(strict_types=1);

namespace Rasuvaeff\PropertyTesting\Tests\Internal;

use Rasuvaeff\PropertyTesting\Internal\ValueRenderer;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Data\DataProvider;
use Testo\Test;

#[Test]
#[Covers(ValueRenderer::class)]
final class ValueRendererTest
{
    #[DataProvider('scalarProvider')]
    public function rendersScalars(mixed $value, string $expected): void
    {
        Assert::same(ValueRenderer::render($value), $expected);
    }

    public static function scalarProvider(): iterable
    {
        yield 'null' => [null, 'null'];
        yield 'true' => [true, 'true'];
        yield 'false' => [false, 'false'];
        yield 'int' => [42, '42'];
        yield 'negative int' => [-7, '-7'];
        yield 'float' => [3.5, '3.5'];
        yield 'nan' => [fdiv(0.0, 0.0), 'NAN'];
        yield 'inf' => [INF, 'INF'];
        yield 'negative inf' => [-INF, '-INF'];
        yield 'negative zero' => [-0.0, '-0.0'];
        yield 'positive zero stays plain' => [0.0, '0'];
        yield 'short string' => ['abc', '"abc"'];
        yield 'empty list' => [[], '[]'];
    }

    public function rendersResourcesViaDebugType(): void
    {
        // A resource matches none of the scalar/array/object arms, so it exercises
        // the match's default (get_debug_type) arm.
        $resource = fopen('php://memory', 'r');
        \assert($resource !== false);

        try {
            Assert::same(ValueRenderer::render($resource), 'resource (stream)');
        } finally {
            fclose($resource);
        }
    }

    // --- strings ---------------------------------------------------------

    public function keepsAStringAtTheLengthLimitVerbatim(): void
    {
        // Exactly MAX_STRING (64) chars: no truncation (the boundary is `>`).
        $value = str_repeat('a', 64);

        Assert::same(ValueRenderer::render($value), '"' . $value . '"');
    }

    public function truncatesAStringOverTheLimitToExactlyTheLimit(): void
    {
        Assert::same(
            ValueRenderer::render(str_repeat('a', 100)),
            '"' . str_repeat('a', 64) . '…" (100 chars)',
        );
    }

    public function truncationKeepsThePrefixFromOffsetZero(): void
    {
        // A distinguishable first character pins the substring offset: keeping the
        // suffix (offset 1) or dropping it would change the visible prefix.
        Assert::same(
            ValueRenderer::render('X' . str_repeat('y', 100)),
            '"X' . str_repeat('y', 63) . '…" (101 chars)',
        );
    }

    public function countsCharactersNotBytesForMultibyteStrings(): void
    {
        // 40 two-byte chars = 80 bytes but only 40 characters — under the limit,
        // so no truncation (a byte-length check would wrongly truncate).
        $value = str_repeat('я', 40);

        Assert::same(ValueRenderer::render($value), '"' . $value . '"');
    }

    public function truncatesMultibyteStringsByCharacterExactly(): void
    {
        Assert::same(
            ValueRenderer::render(str_repeat('я', 70)),
            '"' . str_repeat('я', 64) . '…" (70 chars)',
        );
    }

    // --- binary strings --------------------------------------------------

    public function rendersShortBinaryStringsAsHexExactly(): void
    {
        Assert::same(ValueRenderer::render("\xff\x00\x41"), 'b"0xff0041" (3 byte(s))');
    }

    public function keepsBinaryAtTheLimitWithoutEllipsis(): void
    {
        // Exactly 64 bytes: full hex, no ellipsis (the boundary is `>`).
        $value = str_repeat("\xff", 64);

        Assert::same(ValueRenderer::render($value), 'b"0x' . str_repeat('ff', 64) . '" (64 byte(s))');
    }

    public function truncatesBinaryOverTheLimitToExactlyTheLimit(): void
    {
        $value = str_repeat("\xff", 65);

        Assert::same(ValueRenderer::render($value), 'b"0x' . str_repeat('ff', 64) . '…" (65 byte(s))');
    }

    // --- arrays ----------------------------------------------------------

    public function rendersListsAndMapsExactly(): void
    {
        Assert::same(ValueRenderer::render([1, 2, 3]), '[1, 2, 3]');
        Assert::same(ValueRenderer::render(['a' => 1, 'b' => 2]), '["a" => 1, "b" => 2]');
    }

    public function rendersIntegerMapKeysAsStrings(): void
    {
        // A non-sequential integer key makes this a map, not a list.
        Assert::same(ValueRenderer::render([5 => 'x']), '[5 => "x"]');
    }

    public function keepsExactlyMaxElementsWithoutSummary(): void
    {
        Assert::same(ValueRenderer::render(range(1, 8)), '[1, 2, 3, 4, 5, 6, 7, 8]');
    }

    public function capsElementCountAndCountsTheRemainderExactly(): void
    {
        Assert::same(
            ValueRenderer::render(range(1, 12)),
            '[1, 2, 3, 4, 5, 6, 7, 8, … +4 more]',
        );
    }

    public function capsNestingDepthForLists(): void
    {
        Assert::same(
            ValueRenderer::render([[[[['deep']]]]]),
            '[[[[[… 1 element(s)]]]]]',
        );
    }

    public function capsNestingDepthForMaps(): void
    {
        Assert::same(
            ValueRenderer::render(['a' => ['b' => ['c' => ['d' => ['e' => 1]]]]]),
            '["a" => ["b" => ["c" => ["d" => [… 1 element(s)]]]]]',
        );
    }

    // --- objects ---------------------------------------------------------

    public function rendersEnumCases(): void
    {
        Assert::same(ValueRenderer::render(RenderColor::Red), RenderColor::class . '::Red');
    }

    public function rendersDateTimeInFullPrecision(): void
    {
        Assert::same(
            ValueRenderer::render(new \DateTimeImmutable('@0')),
            '1970-01-01 00:00:00.000000+00:00',
        );
    }

    public function rendersStringableVerbatimUnquoted(): void
    {
        $stringable = new class implements \Stringable {
            #[\Override]
            public function __toString(): string
            {
                return 'TRACE';
            }
        };

        Assert::same(ValueRenderer::render($stringable), 'TRACE');
    }

    public function rendersSimpleObjectProperties(): void
    {
        $object = new \stdClass();
        $object->x = 1;
        $object->label = 'hi';

        Assert::same(ValueRenderer::render($object), 'stdClass {x: 1, label: "hi"}');
    }

    public function rendersEmptyObject(): void
    {
        Assert::same(ValueRenderer::render(new \stdClass()), 'stdClass {}');
    }

    public function keepsExactlyMaxObjectPropertiesWithoutSummary(): void
    {
        $object = new \stdClass();
        for ($i = 0; $i < 8; ++$i) {
            $object->{'p' . $i} = $i;
        }

        Assert::same(
            ValueRenderer::render($object),
            'stdClass {p0: 0, p1: 1, p2: 2, p3: 3, p4: 4, p5: 5, p6: 6, p7: 7}',
        );
    }

    public function capsObjectPropertyCountAndCountsTheRemainder(): void
    {
        $object = new \stdClass();
        for ($i = 0; $i < 10; ++$i) {
            $object->{'p' . $i} = $i;
        }

        Assert::same(
            ValueRenderer::render($object),
            'stdClass {p0: 0, p1: 1, p2: 2, p3: 3, p4: 4, p5: 5, p6: 6, p7: 7, … +2 more}',
        );
    }

    public function capsNestingDepthForObjectAtTheFloor(): void
    {
        $object = new \stdClass();
        $object->x = 1;

        // The object sits at depth 0 (four array levels deep): its properties are
        // elided even though it is non-empty.
        Assert::same(ValueRenderer::render([[[[$object]]]]), '[[[[stdClass {…}]]]]');
    }

    public function capsNestingDepthThroughObjectProperties(): void
    {
        $leaf = new \stdClass();
        $leaf->x = 1;
        $node = $leaf;
        for ($i = 0; $i < 4; ++$i) {
            $wrapper = new \stdClass();
            $wrapper->x = $node;
            $node = $wrapper;
        }

        Assert::same(
            ValueRenderer::render($node),
            'stdClass {x: stdClass {x: stdClass {x: stdClass {x: stdClass {…}}}}}',
        );
    }

    public function guardsAgainstObjectCyclesShowingClassAndMarker(): void
    {
        $object = new \stdClass();
        $object->self = $object;

        Assert::same(ValueRenderer::render($object), 'stdClass {self: stdClass {*recursion*}}');
    }

    public function rendersPrivateAndPromotedDtoProperties(): void
    {
        // Real DTOs use private constructor-promoted properties, which
        // get_object_vars() would hide from outside the class.
        Assert::same(
            ValueRenderer::render(new RenderDto(7, 'x')),
            RenderDto::class . ' {id: 7, name: "x"}',
        );
    }

    public function escapesQuotesNewlinesAndControlCharacters(): void
    {
        // "a", newline, quote, "b", tab — must stay on one unambiguous line.
        Assert::same(ValueRenderer::render("a\n\"b\t"), '"a\n\"b\t"');
    }

    public function rendersEmptyListAtDepthFloorAsEmpty(): void
    {
        // An empty array at the depth floor is still "[]", not the depth-elision
        // marker (the empty-check precedes the depth check).
        Assert::same(ValueRenderer::render([[[[[]]]]]), '[[[[[]]]]]');
    }

    public function rendersEmptyObjectAtDepthFloorAsEmpty(): void
    {
        Assert::same(ValueRenderer::render([[[[new \stdClass()]]]]), '[[[[stdClass {}]]]]');
    }
}

enum RenderColor
{
    case Red;
    case Blue;
}

final class RenderDto
{
    // A static property must be excluded from the rendered instance state.
    private static int $rendered = 0;

    public function __construct(
        private int $id,
        private string $name,
    ) {
        ++self::$rendered;
    }
}
