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
        yield 'short string' => ['abc', '"abc"'];
        yield 'empty list' => [[], '[]'];
    }

    public function truncatesLongStringsAndReportsLength(): void
    {
        $rendered = ValueRenderer::render(str_repeat('a', 100));

        Assert::string($rendered)->contains('…');
        Assert::string($rendered)->contains('(100 chars)');
    }

    public function rendersBinaryStringsAsHexWithByteCount(): void
    {
        $rendered = ValueRenderer::render("\xff\xfe\x00");

        Assert::string($rendered)->contains('0x');
        Assert::string($rendered)->contains('(3 byte(s))');
    }

    public function rendersListsAndMaps(): void
    {
        Assert::same(ValueRenderer::render([1, 2, 3]), '[1, 2, 3]');
        Assert::same(ValueRenderer::render(['a' => 1, 'b' => 2]), '["a" => 1, "b" => 2]');
    }

    public function capsElementCount(): void
    {
        $rendered = ValueRenderer::render(range(1, 12));

        Assert::string($rendered)->contains('… +4 more');
    }

    public function capsNestingDepth(): void
    {
        $rendered = ValueRenderer::render([[[[[1]]]]]);

        Assert::string($rendered)->contains('element(s)]');
    }

    public function rendersEnumCases(): void
    {
        Assert::same(ValueRenderer::render(RenderColor::Red), RenderColor::class . '::Red');
    }

    public function rendersDateTime(): void
    {
        $rendered = ValueRenderer::render(new \DateTimeImmutable('@0'));

        Assert::string($rendered)->contains('1970-01-01');
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

    public function guardsAgainstObjectCycles(): void
    {
        $object = new \stdClass();
        $object->self = $object;

        Assert::string(ValueRenderer::render($object))->contains('*recursion*');
    }
}

enum RenderColor
{
    case Red;
    case Blue;
}
