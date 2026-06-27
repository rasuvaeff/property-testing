<?php

declare(strict_types=1);

namespace Rasuvaeff\PropertyTesting\Internal;

use Rasuvaeff\PropertyTesting\ArbitraryInterface;
use Rasuvaeff\PropertyTesting\AssumptionSkipped;
use Rasuvaeff\PropertyTesting\CounterExample;
use Rasuvaeff\PropertyTesting\Property;
use Rasuvaeff\PropertyTesting\PropertyViolationException;
use Rasuvaeff\PropertyTesting\Random;
use Testo\Common\Messenger;
use Testo\Core\Context\TestInfo;
use Testo\Core\Context\TestResult;
use Testo\Core\Log\Level;
use Testo\Core\Value\Status;
use Testo\Core\Value\TestType;
use Testo\Pipeline\Attribute\InterceptorOptions;
use Testo\Pipeline\Middleware\TestRunInterceptor;

/**
 * Runs a {@see Property}: generates random arguments, executes the test body
 * {@see Property::$runs} times, and on the first failure shrinks the
 * counterexample to a minimal one.
 *
 * The interceptor self-registers via the {@see Property} attribute's
 * {@see \Testo\Pipeline\Attribute\FallbackInterceptor}, so simply requiring the
 * package is enough — no plugin registration in {@see testo.php} is needed.
 *
 * It sits close to the test function in the pipeline (after data providers,
 * repeat and retry policies) so it owns argument generation for property tests.
 *
 * @api
 */
#[InterceptorOptions(
    order: InterceptorOptions::ORDER_CLOSE_TO_TEST,
    testType: TestType::Test,
)]
final readonly class PropertyInterceptor implements TestRunInterceptor
{
    /**
     * Warn when more than this fraction of runs is discarded via {@see \Rasuvaeff\PropertyTesting\Assume::that()}.
     */
    private const float SKIP_RATE_WARNING_THRESHOLD = 0.9;

    public function __construct(
        private Messenger $messenger,
    ) {}

    /**
     * @param callable(TestInfo): TestResult $next
     */
    #[\Override]
    public function runTest(TestInfo $info, callable $next): TestResult
    {
        $reflection = $info->testDefinition->reflection;

        if (!$reflection instanceof \ReflectionMethod) {
            // Property tests need a generators method on the test case class.
            return $next($info);
        }

        $attributes = $reflection->getAttributes(Property::class, \ReflectionAttribute::IS_INSTANCEOF);
        if ($attributes === []) {
            return $next($info);
        }

        $property = $attributes[0]->newInstance();

        $generators = $this->resolveGenerators($reflection, $info, $property);
        $this->validateGenerators($reflection, $generators);

        $parameterNames = array_map(
            static fn(\ReflectionParameter $p): string => $p->getName(),
            $reflection->getParameters(),
        );

        $seed = $property->seed ?? random_int(0, PHP_INT_MAX);
        $random = new Random($seed);

        $skips = 0;
        $checks = 0;

        for ($run = 1; $run <= $property->runs; ++$run) {
            $arguments = $this->generate($generators, $parameterNames, $random);
            $result = $next($info->with(arguments: array_values($arguments)));

            // A discarded run is neither a failure nor a check.
            if ($result->failure instanceof AssumptionSkipped) {
                ++$skips;

                continue;
            }

            if ($result->status->isFailure()) {
                [$shrunk, $shrinkSteps] = $this->shrink($info, $next, $generators, $parameterNames, $arguments);

                return new TestResult(
                    info: $info,
                    status: Status::Failed,
                    failure: new PropertyViolationException(new CounterExample(
                        seed: $seed,
                        runsBeforeFailure: $checks,
                        originalArguments: $arguments,
                        shrunkArguments: $shrunk,
                        shrinkSteps: $shrinkSteps,
                        failure: $result->failure,
                        skips: $skips,
                    )),
                );
            }

            ++$checks;
        }

        $this->warnOnExcessiveSkips($info->name, $skips, $property->runs);

        return new TestResult(
            info: $info,
            status: Status::Passed,
        );
    }

    /**
     * @param array<string, ArbitraryInterface> $generators
     * @param list<string> $parameterNames
     * @return array<string, mixed>
     */
    private function generate(array $generators, array $parameterNames, Random $random): array
    {
        return array_combine(
            $parameterNames,
            array_map(
                static fn(string $name): mixed => $generators[$name]->generate($random),
                $parameterNames,
            ),
        );
    }

    /**
     * @return array<string, ArbitraryInterface>
     */
    private function resolveGenerators(\ReflectionMethod $testMethod, TestInfo $info, Property $property): array
    {
        $methodName = $property->generators ?? $testMethod->getName() . 'Generators';
        $class = $testMethod->getDeclaringClass();

        if (!$class->hasMethod($methodName)) {
            throw new \InvalidArgumentException(sprintf(
                'Property "%s" requires a generators method "%s" on %s returning array<string, ArbitraryInterface>',
                $testMethod->getName(),
                $methodName,
                $class->getName(),
            ));
        }

        $generatorMethod = $class->getMethod($methodName);

        /** @var mixed $generators */
        $generators = $generatorMethod->isStatic()
            ? $generatorMethod->getClosure()()
            : $generatorMethod->getClosure($info->caseInfo->instance?->getInstance())();

        if (!is_array($generators)) {
            throw new \InvalidArgumentException(sprintf(
                'Generators method "%s" must return an array, got %s',
                $methodName,
                get_debug_type($generators),
            ));
        }

        /** @var array<string, ArbitraryInterface> $typed */
        $typed = [];
        foreach ($generators as $name => $generator) {
            if (!$generator instanceof ArbitraryInterface) {
                throw new \InvalidArgumentException(sprintf(
                    'Generators method "%s" must return array<string, ArbitraryInterface>, got %s for key "%s"',
                    $methodName,
                    get_debug_type($generator),
                    (string) $name,
                ));
            }
            $typed[(string) $name] = $generator;
        }

        return $typed;
    }

    /**
     * @param array<string, ArbitraryInterface> $generators
     */
    private function validateGenerators(\ReflectionMethod $testMethod, array $generators): void
    {
        foreach ($testMethod->getParameters() as $parameter) {
            $name = $parameter->getName();

            if (!array_key_exists($name, $generators)) {
                throw new \InvalidArgumentException(sprintf(
                    'No generator for parameter "%s"',
                    $name,
                ));
            }
        }
    }

    /**
     * Greedy per-parameter shrinking: repeatedly try smaller candidates for one
     * parameter at a time, accept the first one that still fails, and keep
     * iterating until a full pass produces no improvement.
     *
     * @param array<string, ArbitraryInterface> $generators
     * @param list<string> $parameterNames
     * @param array<string, mixed> $failingArguments
     * @param callable(TestInfo): TestResult $next
     * @return array{0: array<string, mixed>, 1: int} The minimised arguments and the number of accepted shrink steps.
     */
    private function shrink(
        TestInfo $info,
        callable $next,
        array $generators,
        array $parameterNames,
        array $failingArguments,
    ): array {
        /** @var array<string, mixed> $current */
        $current = $failingArguments;
        $steps = 0;

        do {
            $improved = false;

            foreach ($parameterNames as $name) {
                /** @var mixed $candidate */
                foreach ($generators[$name]->shrink($current[$name]) as $candidate) {
                    // A candidate equal to the current value makes no progress and would loop.
                    if ($candidate === $current[$name]) {
                        continue;
                    }

                    // Replace one parameter in place: array_replace keeps $current's
                    // key order, so array_values() still maps each value to the right
                    // parameter. Array union ([$name => ...] + $current) would move
                    // $name to the front and scramble non-leading parameters.
                    $trial = array_replace($current, [$name => $candidate]);

                    if ($this->runFails($info, $next, $trial)) {
                        $current = $trial;
                        ++$steps;
                        $improved = true;

                        break;
                    }
                }
            }
        } while ($improved);

        return [$current, $steps];
    }

    /**
     * @param array<string, mixed> $arguments
     * @param callable(TestInfo): TestResult $next
     */
    private function runFails(TestInfo $base, callable $next, array $arguments): bool
    {
        $result = $next($base->with(arguments: array_values($arguments)));

        if ($result->failure instanceof AssumptionSkipped) {
            return false;
        }

        return $result->status->isFailure();
    }

    private function warnOnExcessiveSkips(string $name, int $skips, int $runs): void
    {
        if ($runs <= 0 || ($skips / $runs) <= self::SKIP_RATE_WARNING_THRESHOLD) {
            return;
        }

        $this->messenger->log(
            Messenger::CHANNEL_STDERR,
            sprintf(
                'Property "%s" discarded %d of %d runs (%d%%); consider narrowing the generators',
                $name,
                $skips,
                $runs,
                (int) round(((float) $skips / (float) $runs) * 100.0),
            ),
            Level::Warning,
        );
    }
}
