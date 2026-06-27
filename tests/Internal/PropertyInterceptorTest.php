<?php

declare(strict_types=1);

namespace Rasuvaeff\PropertyTesting\Tests\Internal;

use Psr\EventDispatcher\EventDispatcherInterface;
use Rasuvaeff\PropertyTesting\AssumptionSkipped;
use Rasuvaeff\PropertyTesting\Internal\PropertyInterceptor;
use Rasuvaeff\PropertyTesting\PropertyViolationException;
use Testo\Application\Internal\MessengerHub;
use Testo\Assert;
use Testo\Assert\ExpectException;
use Testo\Codecov\Covers;
use Testo\Common\Messenger;
use Testo\Core\Context\CaseInfo;
use Testo\Core\Context\TestInfo;
use Testo\Core\Context\TestResult;
use Testo\Core\Definition\CaseDefinition;
use Testo\Core\Definition\TestDefinition;
use Testo\Core\Value\Status;
use Testo\Test;

#[Test]
#[Covers(PropertyInterceptor::class)]
final class PropertyInterceptorTest
{
    public function passesEveryRunAndReportsPassed(): void
    {
        $interceptor = new PropertyInterceptor($this->createMessenger());
        $calls = 0;
        $next = static function (TestInfo $info) use (&$calls): TestResult {
            ++$calls;
            $value = $info->arguments[0];

            Assert::true($value >= 1 && $value <= 10);

            return new TestResult(info: $info, status: Status::Passed);
        };

        $result = $interceptor->runTest($this->info(PassingStub::class, 'check'), $next);

        Assert::same($calls, 5);
        Assert::same($result->status, Status::Passed);
    }

    public function usesConventionMethodWhenGeneratorsNameIsOmitted(): void
    {
        $interceptor = new PropertyInterceptor($this->createMessenger());
        $calls = 0;
        $next = static function (TestInfo $info) use (&$calls): TestResult {
            ++$calls;

            return new TestResult(info: $info, status: Status::Passed);
        };

        $interceptor->runTest($this->info(ConventionStub::class, 'check'), $next);

        Assert::same($calls, 3);
    }

    public function falsifiesAndShrinksToMinimalCounterexample(): void
    {
        $interceptor = new PropertyInterceptor($this->createMessenger());
        // Property fails iff x > 50; the generator always produces such values,
        // so the first run is the original counterexample and shrinking lands on
        // the smallest value in range that still fails (51).
        $next = static fn(TestInfo $info): TestResult => $info->arguments[0] > 50
            ? new TestResult(info: $info, status: Status::Failed, failure: new \RuntimeException('x>50'))
            : new TestResult(info: $info, status: Status::Passed);

        $result = $interceptor->runTest($this->info(FalsifyingStub::class, 'check'), $next);

        Assert::same($result->status, Status::Failed);
        Assert::instanceOf($result->failure, PropertyViolationException::class);

        $counterExample = $result->failure->getCounterExample();
        Assert::same($counterExample->shrunkArguments['x'], 51);
        Assert::same($counterExample->originalArguments['x'] > 50, true);
        Assert::same($counterExample->runsBeforeFailure, 0);
    }

    public function shrinksTheFailingParameterInAMultiParameterProperty(): void
    {
        $interceptor = new PropertyInterceptor($this->createMessenger());
        // Fails iff the SECOND parameter exceeds 50, regardless of the first.
        // Shrinking must drive `b` to its minimal failing value (51) without
        // scrambling the positional arguments (the union-operator bug fed `a`
        // into `b`'s position, so `b` never shrank).
        $next = static fn(TestInfo $info): TestResult => $info->arguments[1] > 50
            ? new TestResult(info: $info, status: Status::Failed, failure: new \RuntimeException('b>50'))
            : new TestResult(info: $info, status: Status::Passed);

        $result = $interceptor->runTest($this->info(MultiParamFalsifyingStub::class, 'check'), $next);

        Assert::same($result->status, Status::Failed);
        Assert::instanceOf($result->failure, PropertyViolationException::class);

        $counterExample = $result->failure->getCounterExample();
        Assert::same($counterExample->shrunkArguments['b'], 51);
        Assert::same($counterExample->shrunkArguments['a'], 0);
        Assert::true($counterExample->shrinkSteps >= 1);
    }


    public function fixedSeedReproducesTheSameCounterexample(): void
    {
        $interceptor = new PropertyInterceptor($this->createMessenger());
        $next = static fn(TestInfo $info): TestResult => $info->arguments[0] > 50
            ? new TestResult(info: $info, status: Status::Failed, failure: new \RuntimeException('x>50'))
            : new TestResult(info: $info, status: Status::Passed);

        $first = $interceptor->runTest($this->info(FalsifyingStub::class, 'check'), $next);
        $second = $interceptor->runTest($this->info(FalsifyingStub::class, 'check'), $next);

        Assert::instanceOf($first->failure, PropertyViolationException::class);
        Assert::instanceOf($second->failure, PropertyViolationException::class);
        Assert::same($first->failure->getCounterExample()->seed, $second->failure->getCounterExample()->seed);
        Assert::same($first->failure->getCounterExample()->originalArguments, $second->failure->getCounterExample()->originalArguments);
        Assert::same($first->failure->getCounterExample()->shrunkArguments, $second->failure->getCounterExample()->shrunkArguments);
    }

    public function discardedRunsBeforeFailureAreTrackedSeparately(): void
    {
        $interceptor = new PropertyInterceptor($this->createMessenger());
        $calls = 0;
        $next = static function (TestInfo $info) use (&$calls): TestResult {
            ++$calls;

            if ($calls === 1) {
                return new TestResult(info: $info, status: Status::Error, failure: new AssumptionSkipped());
            }

            return new TestResult(info: $info, status: Status::Failed, failure: new \RuntimeException('boom'));
        };

        $result = $interceptor->runTest($this->info(PassingStub::class, 'check'), $next);

        Assert::instanceOf($result->failure, PropertyViolationException::class);

        $counterExample = $result->failure->getCounterExample();
        Assert::same($counterExample->runsBeforeFailure, 0);
        Assert::same($counterExample->skips, 1);
    }

    public function warnsWhenAssumptionsDiscardMoreThanNinetyPercentOfRuns(): void
    {
        $messenger = $this->createMessenger();
        $interceptor = new PropertyInterceptor($messenger);
        $next = static fn(TestInfo $info): TestResult => new TestResult(
            info: $info,
            status: Status::Error,
            failure: new AssumptionSkipped(),
        );

        $result = $interceptor->runTest($this->info(PassingStub::class, 'check'), $next);
        $messages = $messenger->getMessages()->channel(Messenger::CHANNEL_STDERR);

        Assert::same($result->status, Status::Passed);
        Assert::same(count($messages), 1);
        Assert::string($messages[0]->content)->contains('discarded 5 of 5 runs');
    }

    public function discardsRunsViaAssumeWithoutCountingAsFailure(): void
    {
        $interceptor = new PropertyInterceptor($this->createMessenger());
        $next = static function (TestInfo $info): TestResult {
            $value = $info->arguments[0];

            // Keep only runs where the generated value is exactly 5; discard the rest.
            if ($value !== 5) {
                return new TestResult(info: $info, status: Status::Error, failure: new AssumptionSkipped());
            }

            return new TestResult(info: $info, status: Status::Passed);
        };

        $result = $interceptor->runTest($this->info(PassingStub::class, 'check'), $next);

        Assert::same($result->status, Status::Passed);
    }

    public function passesThroughWhenMethodHasNoPropertyAttribute(): void
    {
        $interceptor = new PropertyInterceptor($this->createMessenger());
        $called = false;
        $next = static function (TestInfo $info) use (&$called): TestResult {
            $called = true;

            return new TestResult(info: $info, status: Status::Passed);
        };

        $interceptor->runTest($this->info(PlainStub::class, 'check'), $next);

        Assert::true($called);
    }

    #[ExpectException(\InvalidArgumentException::class)]
    public function throwsWhenGeneratorsMethodIsMissing(): void
    {
        $interceptor = new PropertyInterceptor($this->createMessenger());
        $next = static fn(TestInfo $info): TestResult => new TestResult(info: $info, status: Status::Passed);

        $interceptor->runTest($this->info(MissingGeneratorMethodStub::class, 'check'), $next);
    }

    #[ExpectException(\InvalidArgumentException::class)]
    public function throwsWhenGeneratorMissingForAParameter(): void
    {
        $interceptor = new PropertyInterceptor($this->createMessenger());
        $next = static fn(TestInfo $info): TestResult => new TestResult(info: $info, status: Status::Passed);

        $interceptor->runTest($this->info(MissingParameterGeneratorStub::class, 'check'), $next);
    }

    private function info(string $class, string $method): TestInfo
    {
        $reflection = new \ReflectionMethod($class, $method);

        return new TestInfo(
            name: $method,
            caseInfo: new CaseInfo(definition: new CaseDefinition(name: 'Stub', type: 'test')),
            testDefinition: new TestDefinition(reflection: $reflection),
        );
    }

    private function createMessenger(): Messenger
    {
        return new MessengerHub(new class implements EventDispatcherInterface {
            #[\Override]
            public function dispatch(object $event): object
            {
                return $event;
            }
        });
    }
}
