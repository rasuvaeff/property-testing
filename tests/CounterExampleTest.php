<?php

declare(strict_types=1);

namespace Rasuvaeff\PropertyTesting\Tests;

use Rasuvaeff\PropertyTesting\CounterExample;
use Testo\Assert;
use Testo\Assert\ExpectException;
use Testo\Codecov\Covers;
use Testo\Test;

#[Test]
#[Covers(CounterExample::class)]
final class CounterExampleTest
{
    public function storesAllFieldsVerbatim(): void
    {
        $failure = new \RuntimeException('boom');
        $counterExample = new CounterExample(
            seed: 42,
            runsBeforeFailure: 7,
            originalArguments: ['x' => 100],
            shrunkArguments: ['x' => 3],
            failure: $failure,
            skips: 2,
        );

        Assert::same($counterExample->seed, 42);
        Assert::same($counterExample->runsBeforeFailure, 7);
        Assert::same($counterExample->originalArguments, ['x' => 100]);
        Assert::same($counterExample->shrunkArguments, ['x' => 3]);
        Assert::same($counterExample->failure, $failure);
        Assert::same($counterExample->skips, 2);
    }

    public function defaultsFailureAndSkipsToNullAndZero(): void
    {
        $counterExample = new CounterExample(
            seed: 1,
            runsBeforeFailure: 1,
            originalArguments: [],
            shrunkArguments: [],
        );

        Assert::null($counterExample->failure);
        Assert::same($counterExample->skips, 0);
    }

    public function exposesMachineReadableArrayAndJsonRepresentations(): void
    {
        $counterExample = new CounterExample(
            seed: 42,
            runsBeforeFailure: 3,
            originalArguments: ['dto' => new CounterExampleDto(7)],
            shrunkArguments: ['value' => NAN],
            failure: new \RuntimeException('boom'),
            skips: 2,
        );

        $data = $counterExample->toArray();
        Assert::same($data['seed'], 42);
        Assert::same($data['originalArguments']['dto']['properties']['id'], 7);
        Assert::same($data['shrunkArguments']['value'], 'NAN');
        Assert::same($data['failure'], ['type' => \RuntimeException::class, 'message' => 'boom']);

        $decoded = json_decode($counterExample->toJson(), true, flags: JSON_THROW_ON_ERROR);
        Assert::same($decoded, $data);
    }

    public function createsRunnableExamplesMethodCode(): void
    {
        $counterExample = new CounterExample(
            seed: 42,
            runsBeforeFailure: 3,
            originalArguments: [],
            shrunkArguments: ['x' => 1, 'input' => ['a', true]],
        );

        Assert::same(
            $counterExample->toExamplesCode('holdsExamples'),
            "public static function holdsExamples(): iterable\n{\n    yield 'shrunk seed 42' => [1, ['a', true]];\n}",
        );
    }

    public function usesTheDefaultExamplesMethodNameAndCanPrettyPrintJson(): void
    {
        $counterExample = new CounterExample(42, 0, [], ['x' => 1]);

        Assert::string($counterExample->toExamplesCode())->contains('function propertyExamples()');
        Assert::string($counterExample->toJson(pretty: true))->contains("\n");
    }

    #[ExpectException(\InvalidArgumentException::class)]
    public function rejectsInvalidExamplesMethodName(): void
    {
        (new CounterExample(1, 0, [], []))->toExamplesCode('not-valid');
    }

    #[ExpectException(\LogicException::class)]
    public function refusesToGenerateNonRunnableObjectExampleCode(): void
    {
        (new CounterExample(1, 0, [], ['dto' => new CounterExampleDto(1)]))->toExamplesCode('examples');
    }
}

final readonly class CounterExampleDto
{
    public function __construct(
        private int $id,
    ) {}

    public function getId(): int
    {
        return $this->id;
    }
}
