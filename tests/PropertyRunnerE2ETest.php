<?php

declare(strict_types=1);

namespace Rasuvaeff\PropertyTesting\Tests;

use Rasuvaeff\PropertyTesting\PropertyViolationException;
use Rasuvaeff\PropertyTesting\Tests\Fixture\AssumeDiscardFixture;
use Rasuvaeff\PropertyTesting\Tests\Fixture\FalsifyingPropertyFixture;
use Testo\Assert;
use Testo\Codecov\CoversNothing;
use Testo\Core\Value\Status;
use Testo\Test;
use Testo\Testing\Attribute\TestingSuite;
use Testo\Testing\Helper\TestRunner;

/**
 * End-to-end coverage of the falsify / shrink / Assume loop driven through the
 * real Testo runner — not hand-built TestResult mocks like
 * {@see \Rasuvaeff\PropertyTesting\Tests\Internal\PropertyInterceptorTest}.
 *
 * It proves three things that only the real pipeline can confirm: the
 * #[Property] attribute self-registers (no plugin wiring), a thrown assertion is
 * routed into a {@see PropertyViolationException} that survives Testo's
 * Error->Failed conversion, and {@see \Rasuvaeff\PropertyTesting\Assume::that()}
 * discards runs without failing the property.
 */
#[Test]
#[CoversNothing]
#[TestingSuite(path: __DIR__ . '/Fixture')]
final class PropertyRunnerE2ETest
{
    public function falsifiesAndShrinksThroughTheRealRunner(): void
    {
        $result = TestRunner::runTest([FalsifyingPropertyFixture::class, 'everyValueIsAtMostFifty']);

        Assert::true($result->status->isFailure());
        Assert::instanceOf($result->failure, PropertyViolationException::class);

        $counterExample = $result->failure->getCounterExample();
        // Generator draws from [51, 100]; shrinking clamps toward the range, so
        // the minimal still-failing value is the lower bound, 51.
        Assert::same($counterExample->shrunkArguments['x'], 51);
        Assert::true($counterExample->originalArguments['x'] > 50);
    }

    public function surfacesTheUnderlyingAssertionFailure(): void
    {
        $result = TestRunner::runTest([FalsifyingPropertyFixture::class, 'everyValueIsAtMostFifty']);

        Assert::instanceOf($result->failure, PropertyViolationException::class);
        // The exception chains the real Testo assertion failure as `previous`
        // and renders a "Failure:" line, so the developer sees what broke.
        Assert::same($result->failure->getPrevious(), $result->failure->getCounterExample()->failure);
        Assert::string($result->failure->getMessage())->contains('Failure:');
    }

    public function assumeDiscardsRunsThroughTheRealRunner(): void
    {
        // x ranges over [-50, 50]; non-positive draws are discarded via Assume,
        // so the property holds and the test passes. If AssumptionSkipped were
        // not recognised, those draws would hit Assert::true($x > 0) and fail.
        $result = TestRunner::runTest([AssumeDiscardFixture::class, 'holdsOnlyForPositiveValues']);

        Assert::same($result->status, Status::Passed);
    }
}
