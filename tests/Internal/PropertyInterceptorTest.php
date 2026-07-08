<?php

declare(strict_types=1);

namespace Rasuvaeff\PropertyTesting\Tests\Internal;

use Psr\EventDispatcher\EventDispatcherInterface;
use Rasuvaeff\PropertyTesting\AssumptionSkipped;
use Rasuvaeff\PropertyTesting\Classify;
use Rasuvaeff\PropertyTesting\CoverageViolationException;
use Rasuvaeff\PropertyTesting\ExampleViolationException;
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

    public function reportsTheFailureOfTheShrunkCounterexampleNotTheOriginal(): void
    {
        $interceptor = new PropertyInterceptor($this->createMessenger());
        // The failure message encodes the failing value, so the reported failure
        // reveals which run it came from. Shrinking drives x to 51, and the
        // reported failure must be that minimal run's, not the original draw's.
        $next = static fn(TestInfo $info): TestResult => $info->arguments[0] > 50
            ? new TestResult(info: $info, status: Status::Failed, failure: new \RuntimeException('failed at ' . $info->arguments[0]))
            : new TestResult(info: $info, status: Status::Passed);

        $result = $interceptor->runTest($this->info(FalsifyingStub::class, 'check'), $next);

        Assert::instanceOf($result->failure, PropertyViolationException::class);

        $counterExample = $result->failure->getCounterExample();
        Assert::same($counterExample->shrunkArguments['x'], 51);
        Assert::true($counterExample->shrinkSteps >= 1);
        Assert::instanceOf($counterExample->failure, \RuntimeException::class);
        Assert::string($counterExample->failure->getMessage())->contains('failed at 51');
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


    public function mappedGeneratorShrinksThroughTheSourceDomain(): void
    {
        $interceptor = new PropertyInterceptor($this->createMessenger());
        // Values are doubled ints; fails iff x > 50. The minimal even value that
        // still fails is 52 — reachable only by shrinking the source int (26)
        // and re-applying the mapping. Pre-2.0 map() reported the original
        // counterexample unshrunk.
        $next = static fn(TestInfo $info): TestResult => $info->arguments[0] > 50
            ? new TestResult(info: $info, status: Status::Failed, failure: new \RuntimeException('x>50'))
            : new TestResult(info: $info, status: Status::Passed);

        $result = $interceptor->runTest($this->info(MappedFalsifyingStub::class, 'check'), $next);

        Assert::instanceOf($result->failure, PropertyViolationException::class);
        Assert::same($result->failure->getCounterExample()->shrunkArguments['x'], 52);
    }

    public function flatMapGeneratorShrinksTheDependentValue(): void
    {
        $interceptor = new PropertyInterceptor($this->createMessenger());
        // The dependent value m lies in [0, n]; fails iff m > 3, so the minimal
        // failing value is 4 regardless of how the source n shrinks.
        $next = static fn(TestInfo $info): TestResult => $info->arguments[0] > 3
            ? new TestResult(info: $info, status: Status::Failed, failure: new \RuntimeException('m>3'))
            : new TestResult(info: $info, status: Status::Passed);

        $result = $interceptor->runTest($this->info(FlatMapFalsifyingStub::class, 'check'), $next);

        Assert::instanceOf($result->failure, PropertyViolationException::class);
        Assert::same($result->failure->getCounterExample()->shrunkArguments['x'], 4);
    }

    public function flatMapCounterexampleIsReproducibleForAFixedSeed(): void
    {
        $interceptor = new PropertyInterceptor($this->createMessenger());
        $next = static fn(TestInfo $info): TestResult => $info->arguments[0] > 3
            ? new TestResult(info: $info, status: Status::Failed, failure: new \RuntimeException('m>3'))
            : new TestResult(info: $info, status: Status::Passed);

        $first = $interceptor->runTest($this->info(FlatMapFalsifyingStub::class, 'check'), $next);
        $second = $interceptor->runTest($this->info(FlatMapFalsifyingStub::class, 'check'), $next);

        Assert::instanceOf($first->failure, PropertyViolationException::class);
        Assert::instanceOf($second->failure, PropertyViolationException::class);
        Assert::same($first->failure->getCounterExample()->originalArguments, $second->failure->getCounterExample()->originalArguments);
        Assert::same($first->failure->getCounterExample()->shrunkArguments, $second->failure->getCounterExample()->shrunkArguments);
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

    public function maxShrinksCapsTheNumberOfAcceptedShrinkSteps(): void
    {
        $interceptor = new PropertyInterceptor($this->createMessenger());
        // The property fails for every input, so without a cap shrinking would
        // accept one step per parameter (two). maxShrinks=1 stops after the first.
        $next = static fn(TestInfo $info): TestResult => new TestResult(
            info: $info,
            status: Status::Failed,
            failure: new \RuntimeException('always fails'),
        );

        $result = $interceptor->runTest($this->info(MaxShrinksCapStub::class, 'check'), $next);

        Assert::instanceOf($result->failure, PropertyViolationException::class);

        $counterExample = $result->failure->getCounterExample();
        Assert::same($counterExample->shrinkSteps, 1);

        // Exactly one accepted step changes exactly one parameter.
        $changed = 0;
        foreach ($counterExample->originalArguments as $name => $original) {
            if ($counterExample->shrunkArguments[$name] !== $original) {
                ++$changed;
            }
        }
        Assert::same($changed, 1);
    }

    public function maxShrinksZeroDisablesShrinking(): void
    {
        $interceptor = new PropertyInterceptor($this->createMessenger());
        $next = static fn(TestInfo $info): TestResult => new TestResult(
            info: $info,
            status: Status::Failed,
            failure: new \RuntimeException('always fails'),
        );

        $result = $interceptor->runTest($this->info(MaxShrinksDisabledStub::class, 'check'), $next);

        Assert::instanceOf($result->failure, PropertyViolationException::class);

        $counterExample = $result->failure->getCounterExample();
        Assert::same($counterExample->shrinkSteps, 0);
        Assert::same($counterExample->shrunkArguments, $counterExample->originalArguments);
    }

    public function envPropertyRunsOverridesTheAttributeRunCount(): void
    {
        putenv('PROPERTY_RUNS=3');

        try {
            $interceptor = new PropertyInterceptor($this->createMessenger());
            $calls = 0;
            $next = static function (TestInfo $info) use (&$calls): TestResult {
                ++$calls;

                return new TestResult(info: $info, status: Status::Passed);
            };

            // PassingStub declares runs: 5; the env var forces 3.
            $interceptor->runTest($this->info(PassingStub::class, 'check'), $next);

            Assert::same($calls, 3);
        } finally {
            putenv('PROPERTY_RUNS');
        }
    }

    public function envPropertySeedSuppliesTheSeedWhenTheAttributeOmitsIt(): void
    {
        putenv('PROPERTY_SEED=777');

        try {
            $interceptor = new PropertyInterceptor($this->createMessenger());
            $next = static fn(TestInfo $info): TestResult => $info->arguments[0] > 50
                ? new TestResult(info: $info, status: Status::Failed, failure: new \RuntimeException('x>50'))
                : new TestResult(info: $info, status: Status::Passed);

            $result = $interceptor->runTest($this->info(NoSeedFalsifyingStub::class, 'check'), $next);

            Assert::instanceOf($result->failure, PropertyViolationException::class);
            Assert::same($result->failure->getCounterExample()->seed, 777);
        } finally {
            putenv('PROPERTY_SEED');
        }
    }

    public function attributeSeedWinsOverTheEnvironmentSeed(): void
    {
        putenv('PROPERTY_SEED=777');

        try {
            $interceptor = new PropertyInterceptor($this->createMessenger());
            $next = static fn(TestInfo $info): TestResult => new TestResult(
                info: $info,
                status: Status::Failed,
                failure: new \RuntimeException('always'),
            );

            // FalsifyingStub declares seed: 1, which must win over the env seed.
            $result = $interceptor->runTest($this->info(FalsifyingStub::class, 'check'), $next);

            Assert::instanceOf($result->failure, PropertyViolationException::class);
            Assert::same($result->failure->getCounterExample()->seed, 1);
        } finally {
            putenv('PROPERTY_SEED');
        }
    }

    #[ExpectException(\InvalidArgumentException::class)]
    public function rejectsNonNumericPropertyRuns(): void
    {
        putenv('PROPERTY_RUNS=abc');

        try {
            $interceptor = new PropertyInterceptor($this->createMessenger());
            $next = static fn(TestInfo $info): TestResult => new TestResult(info: $info, status: Status::Passed);

            $interceptor->runTest($this->info(PassingStub::class, 'check'), $next);
        } finally {
            putenv('PROPERTY_RUNS');
        }
    }

    #[ExpectException(\InvalidArgumentException::class)]
    public function rejectsNonNumericPropertySeed(): void
    {
        putenv('PROPERTY_SEED=abc');

        try {
            $interceptor = new PropertyInterceptor($this->createMessenger());
            $next = static fn(TestInfo $info): TestResult => new TestResult(info: $info, status: Status::Passed);

            $interceptor->runTest($this->info(NoSeedFalsifyingStub::class, 'check'), $next);
        } finally {
            putenv('PROPERTY_SEED');
        }
    }

    public function reportsClassificationDistributionAfterPassingRuns(): void
    {
        $messenger = $this->createMessenger();
        $interceptor = new PropertyInterceptor($messenger);
        $next = static function (TestInfo $info): TestResult {
            Classify::label('checked');

            return new TestResult(info: $info, status: Status::Passed);
        };

        // PassingStub runs 5 times; every run records 'checked'.
        $interceptor->runTest($this->info(PassingStub::class, 'check'), $next);
        $messages = $messenger->getMessages()->channel(Messenger::CHANNEL_STDOUT);

        Assert::same(count($messages), 1);
        Assert::string($messages[0]->content)->contains('checked 100% (5/5)');
    }

    public function coverageRequirementMetKeepsThePropertyPassing(): void
    {
        $interceptor = new PropertyInterceptor($this->createMessenger());
        $next = static function (TestInfo $info): TestResult {
            Classify::cover(true, 'hit', 50.0);

            return new TestResult(info: $info, status: Status::Passed);
        };

        $result = $interceptor->runTest($this->info(PassingStub::class, 'check'), $next);

        Assert::same($result->status, Status::Passed);
    }

    public function coverageRequirementUnmetFailsThePassingProperty(): void
    {
        $interceptor = new PropertyInterceptor($this->createMessenger());
        // Every run passes, but the required label never occurs: the pass is
        // vacuous and must be reported as a failure.
        $next = static function (TestInfo $info): TestResult {
            Classify::cover(false, 'never', 10.0);

            return new TestResult(info: $info, status: Status::Passed);
        };

        $result = $interceptor->runTest($this->info(PassingStub::class, 'check'), $next);

        Assert::same($result->status, Status::Failed);
        Assert::instanceOf($result->failure, CoverageViolationException::class);
        Assert::string($result->failure->getMessage())->contains('"never" 0.0% < required 10.0% (0/5)');
    }

    public function coverageIsExactAtTheRequiredBoundary(): void
    {
        $interceptor = new PropertyInterceptor($this->createMessenger());
        $calls = 0;
        // PassingStub runs 5 times; the label occurs on exactly 2 of 5 runs
        // (40%). A requirement of exactly 40% is met (strictly-below fails).
        $next = static function (TestInfo $info) use (&$calls): TestResult {
            ++$calls;
            Classify::cover($calls <= 2, 'sometimes', 40.0);

            return new TestResult(info: $info, status: Status::Passed);
        };

        $result = $interceptor->runTest($this->info(PassingStub::class, 'check'), $next);

        Assert::same($result->status, Status::Passed);
    }

    public function coverageIgnoresDiscardedRunsInTheDenominator(): void
    {
        $interceptor = new PropertyInterceptor($this->createMessenger());
        $calls = 0;
        // 5 runs: 3 discarded, 2 passing with the label => 100% of checks.
        $next = static function (TestInfo $info) use (&$calls): TestResult {
            ++$calls;

            if ($calls <= 3) {
                return new TestResult(info: $info, status: Status::Error, failure: new AssumptionSkipped());
            }

            Classify::cover(true, 'hit', 90.0);

            return new TestResult(info: $info, status: Status::Passed);
        };

        $result = $interceptor->runTest($this->info(PassingStub::class, 'check'), $next);

        Assert::same($result->status, Status::Passed);
    }

    public function coverageWithoutAnySuccessfulRunFails(): void
    {
        $interceptor = new PropertyInterceptor($this->createMessenger());
        // Every run is discarded but a requirement was registered: there is no
        // evidence the labelled case is reachable, so the property must fail.
        $next = static function (TestInfo $info): TestResult {
            Classify::cover(true, 'unreached', 10.0);

            return new TestResult(info: $info, status: Status::Error, failure: new AssumptionSkipped());
        };

        $result = $interceptor->runTest($this->info(PassingStub::class, 'check'), $next);

        Assert::same($result->status, Status::Failed);
        Assert::instanceOf($result->failure, CoverageViolationException::class);
        Assert::string($result->failure->getMessage())->contains('no successful runs');
    }

    public function falsificationWinsOverCoverage(): void
    {
        $interceptor = new PropertyInterceptor($this->createMessenger());
        // A falsified property reports the counterexample, not the coverage.
        $next = static function (TestInfo $info): TestResult {
            Classify::cover(false, 'never', 10.0);

            return new TestResult(info: $info, status: Status::Failed, failure: new \RuntimeException('boom'));
        };

        $result = $interceptor->runTest($this->info(PassingStub::class, 'check'), $next);

        Assert::instanceOf($result->failure, PropertyViolationException::class);
    }

    public function coverageRequirementsDoNotLeakIntoTheNextProperty(): void
    {
        $interceptor = new PropertyInterceptor($this->createMessenger());
        // First property falsifies with a registered requirement (which is
        // never assessed); the next property without cover() must pass.
        $failing = static function (TestInfo $info): TestResult {
            Classify::cover(false, 'leftover', 99.0);

            return new TestResult(info: $info, status: Status::Failed, failure: new \RuntimeException('boom'));
        };
        $interceptor->runTest($this->info(FalsifyingStub::class, 'check'), $failing);

        $passing = static fn(TestInfo $info): TestResult => new TestResult(info: $info, status: Status::Passed);
        $result = $interceptor->runTest($this->info(PassingStub::class, 'check'), $passing);

        Assert::same($result->status, Status::Passed);
    }

    public function verboseLogsEveryRunsArguments(): void
    {
        putenv('PROPERTY_VERBOSE=1');

        try {
            $messenger = $this->createMessenger();
            $interceptor = new PropertyInterceptor($messenger);
            $next = static fn(TestInfo $info): TestResult => new TestResult(info: $info, status: Status::Passed);

            // PassingStub runs 5 times.
            $interceptor->runTest($this->info(PassingStub::class, 'check'), $next);
            $messages = $messenger->getMessages()->channel(Messenger::CHANNEL_STDOUT);

            Assert::same(count($messages), 5);
            Assert::string($messages[0]->content)->contains('run 1: x=');
            Assert::string($messages[4]->content)->contains('run 5: x=');
        } finally {
            putenv('PROPERTY_VERBOSE');
        }
    }

    public function verboseZeroDisablesTheRunLog(): void
    {
        putenv('PROPERTY_VERBOSE=0');

        try {
            $messenger = $this->createMessenger();
            $interceptor = new PropertyInterceptor($messenger);
            $next = static fn(TestInfo $info): TestResult => new TestResult(info: $info, status: Status::Passed);

            $interceptor->runTest($this->info(PassingStub::class, 'check'), $next);

            Assert::same(count($messenger->getMessages()->channel(Messenger::CHANNEL_STDOUT)), 0);
        } finally {
            putenv('PROPERTY_VERBOSE');
        }
    }

    public function verboseRendersEveryArgumentStyle(): void
    {
        putenv('PROPERTY_VERBOSE=1');

        try {
            $messenger = $this->createMessenger();
            $interceptor = new PropertyInterceptor($messenger);
            $next = static fn(TestInfo $info): TestResult => new TestResult(info: $info, status: Status::Passed);

            // MixedArgumentsStub generates a string, a bool, a null, an array
            // and a datetime — one run pins every branch of the formatter.
            $interceptor->runTest($this->info(MixedArgumentsStub::class, 'check'), $next);
            $messages = $messenger->getMessages()->channel(Messenger::CHANNEL_STDOUT);

            Assert::same(count($messages), 1);
            Assert::string($messages[0]->content)->contains('s="fixed"');
            Assert::string($messages[0]->content)->contains('b=false');
            Assert::string($messages[0]->content)->contains('n=null');
            Assert::string($messages[0]->content)->contains('a=[2 element(s)]');
            Assert::string($messages[0]->content)->contains('d=DateTimeImmutable');
            Assert::string($messages[0]->content)->contains('i=7');
        } finally {
            putenv('PROPERTY_VERBOSE');
        }
    }

    public function verboseRendersStringableArgumentsViaToString(): void
    {
        putenv('PROPERTY_VERBOSE=1');

        try {
            $messenger = $this->createMessenger();
            $interceptor = new PropertyInterceptor($messenger);
            $next = static fn(TestInfo $info): TestResult => new TestResult(info: $info, status: Status::Passed);

            $interceptor->runTest($this->info(StringableArgStub::class, 'check'), $next);
            $messages = $messenger->getMessages()->channel(Messenger::CHANNEL_STDOUT);

            Assert::same(count($messages), 1);
            Assert::string($messages[0]->content)->contains('s=STRINGABLE');
        } finally {
            putenv('PROPERTY_VERBOSE');
        }
    }

    public function reportsNoDistributionWhenNoLabelsRecorded(): void
    {
        $messenger = $this->createMessenger();
        $interceptor = new PropertyInterceptor($messenger);
        $next = static fn(TestInfo $info): TestResult => new TestResult(info: $info, status: Status::Passed);

        $interceptor->runTest($this->info(PassingStub::class, 'check'), $next);

        Assert::same(count($messenger->getMessages()->channel(Messenger::CHANNEL_STDOUT)), 0);
    }

    public function failingExampleShortCircuitsBeforeRandomRuns(): void
    {
        $interceptor = new PropertyInterceptor($this->createMessenger());
        $calls = 0;
        $next = static function (TestInfo $info) use (&$calls): TestResult {
            ++$calls;

            return $info->arguments[0] >= 100
                ? new TestResult(info: $info, status: Status::Failed, failure: new \RuntimeException('too big'))
                : new TestResult(info: $info, status: Status::Passed);
        };

        $result = $interceptor->runTest($this->info(ConventionExampleStub::class, 'check'), $next);

        Assert::same($result->status, Status::Failed);
        Assert::instanceOf($result->failure, ExampleViolationException::class);
        Assert::same($result->failure->getIndex(), 0);
        Assert::same($result->failure->getArguments(), [100]);
        // Only the first example ran; the second example and the random runs did not.
        Assert::same($calls, 1);
    }

    public function passingExamplesRunFirstThenRandomInputs(): void
    {
        $interceptor = new PropertyInterceptor($this->createMessenger());
        $seen = [];
        $next = static function (TestInfo $info) use (&$seen): TestResult {
            $seen[] = $info->arguments[0];

            return new TestResult(info: $info, status: Status::Passed);
        };

        $result = $interceptor->runTest($this->info(ConventionExampleStub::class, 'check'), $next);

        Assert::same($result->status, Status::Passed);
        // Both examples ran first, in order, before the 3 random runs.
        Assert::same($seen[0], 100);
        Assert::same($seen[1], 200);
        Assert::same(count($seen), 5);
    }

    public function attributeNamesTheExamplesMethod(): void
    {
        $interceptor = new PropertyInterceptor($this->createMessenger());
        $next = static fn(TestInfo $info): TestResult => $info->arguments[0] === 5
            ? new TestResult(info: $info, status: Status::Failed, failure: new \RuntimeException('five'))
            : new TestResult(info: $info, status: Status::Passed);

        $result = $interceptor->runTest($this->info(NamedExampleStub::class, 'check'), $next);

        Assert::instanceOf($result->failure, ExampleViolationException::class);
        Assert::same($result->failure->getArguments(), [5]);
    }

    public function exampleFailureRendersIndexAndArguments(): void
    {
        $interceptor = new PropertyInterceptor($this->createMessenger());
        $next = static fn(TestInfo $info): TestResult => $info->arguments[0] >= 100
            ? new TestResult(info: $info, status: Status::Failed, failure: new \RuntimeException('boom'))
            : new TestResult(info: $info, status: Status::Passed);

        $result = $interceptor->runTest($this->info(ConventionExampleStub::class, 'check'), $next);

        Assert::instanceOf($result->failure, ExampleViolationException::class);
        Assert::string($result->failure->getMessage())->contains('Explicit example #0');
        Assert::string($result->failure->getMessage())->contains('100');
        Assert::string($result->failure->getMessage())->contains('Failure:');
    }

    public function discardedExampleIsNotAFailure(): void
    {
        $interceptor = new PropertyInterceptor($this->createMessenger());
        // The first example (100) is discarded via Assume, not failed, so the
        // property proceeds and passes.
        $next = static fn(TestInfo $info): TestResult => $info->arguments[0] >= 100
            ? new TestResult(info: $info, status: Status::Error, failure: new AssumptionSkipped())
            : new TestResult(info: $info, status: Status::Passed);

        $result = $interceptor->runTest($this->info(ConventionExampleStub::class, 'check'), $next);

        Assert::same($result->status, Status::Passed);
    }

    #[ExpectException(\InvalidArgumentException::class)]
    public function throwsWhenExampleArityMismatches(): void
    {
        $interceptor = new PropertyInterceptor($this->createMessenger());
        $next = static fn(TestInfo $info): TestResult => new TestResult(info: $info, status: Status::Passed);

        $interceptor->runTest($this->info(BadArityExampleStub::class, 'check'), $next);
    }

    #[ExpectException(\InvalidArgumentException::class)]
    public function throwsWhenNamedExamplesMethodMissing(): void
    {
        $interceptor = new PropertyInterceptor($this->createMessenger());
        $next = static fn(TestInfo $info): TestResult => new TestResult(info: $info, status: Status::Passed);

        $interceptor->runTest($this->info(MissingExampleMethodStub::class, 'check'), $next);
    }

    #[ExpectException(\InvalidArgumentException::class)]
    public function throwsWhenExampleIsNotAnArray(): void
    {
        $interceptor = new PropertyInterceptor($this->createMessenger());
        $next = static fn(TestInfo $info): TestResult => new TestResult(info: $info, status: Status::Passed);

        $interceptor->runTest($this->info(NonArrayExampleStub::class, 'check'), $next);
    }

    public function recordsFailingSeedWhenStorageEnabled(): void
    {
        $dir = $this->tempStorageDir();
        putenv('PROPERTY_DB=' . $dir);

        try {
            $interceptor = new PropertyInterceptor($this->createMessenger());
            $next = static fn(TestInfo $info): TestResult => new TestResult(
                info: $info,
                status: Status::Failed,
                failure: new \RuntimeException('always'),
            );

            $result = $interceptor->runTest($this->info(NoSeedFalsifyingStub::class, 'check'), $next);

            Assert::instanceOf($result->failure, PropertyViolationException::class);
            $file = $dir . '/' . sha1(NoSeedFalsifyingStub::class . '::check') . '.seed';
            Assert::same(is_file($file), true);
            Assert::same((int) file_get_contents($file), $result->failure->getCounterExample()->seed);
        } finally {
            putenv('PROPERTY_DB');
            $this->cleanupDir($dir);
        }
    }

    public function replaysARecordedSeedFirst(): void
    {
        $dir = $this->tempStorageDir();
        putenv('PROPERTY_DB=' . $dir);

        try {
            // Pre-seed storage with a recorded failing seed; the replay phase must
            // reproduce the failure with THAT seed (not a fresh random one).
            file_put_contents($dir . '/' . sha1(NoSeedFalsifyingStub::class . '::check') . '.seed', '999');

            $interceptor = new PropertyInterceptor($this->createMessenger());
            $next = static fn(TestInfo $info): TestResult => new TestResult(
                info: $info,
                status: Status::Failed,
                failure: new \RuntimeException('always'),
            );

            $result = $interceptor->runTest($this->info(NoSeedFalsifyingStub::class, 'check'), $next);

            Assert::instanceOf($result->failure, PropertyViolationException::class);
            Assert::same($result->failure->getCounterExample()->seed, 999);
        } finally {
            putenv('PROPERTY_DB');
            $this->cleanupDir($dir);
        }
    }

    public function forgetsARecordedSeedWhenTheReplayNoLongerFails(): void
    {
        $dir = $this->tempStorageDir();
        putenv('PROPERTY_DB=' . $dir);

        try {
            $file = $dir . '/' . sha1(NoSeedFalsifyingStub::class . '::check') . '.seed';
            file_put_contents($file, '999');

            $interceptor = new PropertyInterceptor($this->createMessenger());
            $next = static fn(TestInfo $info): TestResult => new TestResult(info: $info, status: Status::Passed);

            $result = $interceptor->runTest($this->info(NoSeedFalsifyingStub::class, 'check'), $next);

            Assert::same($result->status, Status::Passed);
            Assert::same(is_file($file), false);
        } finally {
            putenv('PROPERTY_DB');
            $this->cleanupDir($dir);
        }
    }

    public function attributeSeedDisablesReplay(): void
    {
        $dir = $this->tempStorageDir();
        putenv('PROPERTY_DB=' . $dir);

        try {
            // FalsifyingStub pins seed:1; a stored seed must be ignored so the
            // pinned reproducibility wins.
            file_put_contents($dir . '/' . sha1(FalsifyingStub::class . '::check') . '.seed', '999');

            $interceptor = new PropertyInterceptor($this->createMessenger());
            $next = static fn(TestInfo $info): TestResult => new TestResult(
                info: $info,
                status: Status::Failed,
                failure: new \RuntimeException('always'),
            );

            $result = $interceptor->runTest($this->info(FalsifyingStub::class, 'check'), $next);

            Assert::instanceOf($result->failure, PropertyViolationException::class);
            Assert::same($result->failure->getCounterExample()->seed, 1);
        } finally {
            putenv('PROPERTY_DB');
            $this->cleanupDir($dir);
        }
    }

    public function storageDisabledWritesNothingAndDoesNotCrash(): void
    {
        putenv('PROPERTY_DB');

        $interceptor = new PropertyInterceptor($this->createMessenger());
        $next = static fn(TestInfo $info): TestResult => new TestResult(
            info: $info,
            status: Status::Failed,
            failure: new \RuntimeException('always'),
        );

        $result = $interceptor->runTest($this->info(NoSeedFalsifyingStub::class, 'check'), $next);

        Assert::instanceOf($result->failure, PropertyViolationException::class);
    }

    private function tempStorageDir(): string
    {
        $dir = sys_get_temp_dir() . '/prop-db-' . bin2hex(random_bytes(6));
        mkdir($dir, 0o777, true);

        return $dir;
    }

    private function cleanupDir(string $dir): void
    {
        foreach (glob($dir . '/*') ?: [] as $file) {
            @unlink($file);
        }

        @rmdir($dir);
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
