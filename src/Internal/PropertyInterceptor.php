<?php

declare(strict_types=1);

namespace Rasuvaeff\PropertyTesting\Internal;

use Rasuvaeff\PropertyTesting\ArbitraryInterface;
use Rasuvaeff\PropertyTesting\AssumptionSkipped;
use Rasuvaeff\PropertyTesting\Classify;
use Rasuvaeff\PropertyTesting\CounterExample;
use Rasuvaeff\PropertyTesting\CoverageViolationException;
use Rasuvaeff\PropertyTesting\ExampleViolationException;
use Rasuvaeff\PropertyTesting\GaveUpException;
use Rasuvaeff\PropertyTesting\GenerationExhausted;
use Rasuvaeff\PropertyTesting\Property;
use Rasuvaeff\PropertyTesting\PropertyViolationException;
use Rasuvaeff\PropertyTesting\Random;
use Rasuvaeff\PropertyTesting\Shrinkable;
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
 * until {@see Property::$runs} successful checks complete, and on the first
 * failure shrinks the counterexample to a minimal one.
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

    /**
     * Cap on accepted shrink steps once in-body draws are on the tape. An
     * accepted candidate can change the body's control flow and regrow the
     * tape with fresh trees, so tree depth alone no longer bounds the descent;
     * the cap guarantees termination. An explicit {@see Property::$maxShrinks}
     * still wins.
     */
    private const int MAX_DRAW_SHRINK_STEPS = 1000;

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

        $runs = $this->resolveRuns($property->runs);
        $maxDiscards = $this->resolveMaxDiscards($property->maxDiscards, $runs);
        $seed = $this->resolveSeed($property->seed);
        $verbose = $this->resolveVerbose();
        $random = new Random($seed);

        // Discard requirements and a draw tape a previously aborted property may
        // have left over.
        Classify::flushRequirements();
        DrawContext::disarm();

        $exampleFailure = $this->runExamples($reflection, $info, $next, $property, $seed);
        if ($exampleFailure instanceof TestResult) {
            return $exampleFailure;
        }

        $storage = SeedStorage::fromEnv();
        $propertyId = $reflection->getDeclaringClass()->getName() . '::' . $reflection->getName();

        // Opt-in regression replay: re-run a previously recorded failing seed
        // first (unless the attribute pins its own seed). A reproduced failure is
        // reported immediately; a seed that no longer fails is forgotten.
        if ($storage instanceof SeedStorage && $property->seed === null) {
            $recalled = $storage->recall($propertyId);

            if ($recalled !== null) {
                $replay = $this->runProperty(new Random($recalled), $recalled, $generators, $parameterNames, $runs, $maxDiscards, $verbose, $next, $info, $property->maxShrinks);

                if ($replay->failure instanceof PropertyViolationException) {
                    return $replay;
                }

                $storage->forget($propertyId);
            }
        }

        $result = $this->runProperty($random, $seed, $generators, $parameterNames, $runs, $maxDiscards, $verbose, $next, $info, $property->maxShrinks);

        if ($storage instanceof SeedStorage && $result->failure instanceof PropertyViolationException) {
            $storage->remember($propertyId, $seed);
        }

        return $result;
    }

    /**
     * The random-input phase: generate until {@see $runs} successful checks have
     * completed, shrink the first falsifying run into a
     * {@see PropertyViolationException}, otherwise assess the
     * {@see Classify::cover()} requirements. Runs once per seed, so the
     * regression-replay phase can re-run it with a recorded seed.
     *
     * @param array<string, ArbitraryInterface> $generators
     * @param list<string> $parameterNames
     * @param callable(TestInfo): TestResult $next
     */
    private function runProperty(
        Random $random,
        int $seed,
        array $generators,
        array $parameterNames,
        int $runs,
        int $maxDiscards,
        bool $verbose,
        callable $next,
        TestInfo $info,
        ?int $maxShrinks,
    ): TestResult {
        $skips = 0;
        $checks = 0;
        $attempts = 0;
        /** @var array<string, int> $classifications */
        $classifications = [];
        /**
         * Downstream interceptors attach per-run attributes to each run's
         * TestResult — e.g. Testo's codecov plugin stores its CoverageResult
         * there. The aggregate result this method returns must carry them,
         * otherwise the property test vanishes from per-test coverage and
         * consumers like Infection never select it for mutants. Merged
         * run-by-run (last write per key wins).
         *
         * @var array<non-empty-string, mixed> $runAttributes
         */
        $runAttributes = [];

        while ($checks < $runs) {
            ++$attempts;
            Classify::beginRun();

            try {
                $trees = $this->generate($generators, $parameterNames, $random);
            } catch (GenerationExhausted $exhausted) {
                // A generator could not produce a valid value (e.g. Gen::filter()
                // whose predicate rejected every draw). Report it as a clean
                // failure rather than let it crash the run as an uncaught error.
                Classify::flushRun();
                Classify::flushRequirements();

                return new TestResult(
                    info: $info,
                    status: Status::Failed,
                    failure: $exhausted,
                    attributes: $runAttributes,
                );
            }

            $arguments = $this->values($trees);

            if ($verbose) {
                $this->messenger->log(
                    Messenger::CHANNEL_STDOUT,
                    sprintf('Property "%s" attempt %d: %s', $info->name, $attempts, $this->formatArguments($arguments)),
                    Level::Info,
                );
            }

            DrawContext::arm($random);
            $result = $next($info->with(arguments: array_values($arguments)));
            $draws = DrawContext::disarm();
            $runAttributes = array_merge($runAttributes, $result->attributes);
            $labels = Classify::flushRun();

            if ($verbose && $draws !== []) {
                $this->messenger->log(
                    Messenger::CHANNEL_STDOUT,
                    sprintf('Property "%s" attempt %d draws: %s', $info->name, $attempts, $this->formatArguments($this->drawArguments($draws))),
                    Level::Info,
                );
            }

            // A discarded run is neither a failure nor a check.
            if ($result->failure instanceof AssumptionSkipped) {
                ++$skips;

                if ($skips > $maxDiscards) {
                    $this->warnOnExcessiveSkips($info->name, $skips, $attempts);
                    $this->reportClassifications($info->name, $classifications, $checks);
                    Classify::flushRequirements();

                    return new TestResult(
                        info: $info,
                        status: Status::Failed,
                        failure: new GaveUpException(
                            propertyName: $info->name,
                            requiredRuns: $runs,
                            successfulRuns: $checks,
                            discardedRuns: $skips,
                            attempts: $attempts,
                            maxDiscards: $maxDiscards,
                        ),
                        attributes: $runAttributes,
                    );
                }

                continue;
            }

            if ($result->status->isFailure()) {
                [$shrunk, $shrunkDraws, $shrinkSteps, $shrunkFailure] = $this->shrink($info, $next, $trees, $draws, $random, $maxShrinks);

                return new TestResult(
                    info: $info,
                    status: Status::Failed,
                    failure: new PropertyViolationException(new CounterExample(
                        seed: $seed,
                        runsBeforeFailure: $checks,
                        originalArguments: array_merge($arguments, $this->drawArguments($draws)),
                        shrunkArguments: array_merge($shrunk, $shrunkDraws),
                        shrinkSteps: $shrinkSteps,
                        // Report the failure of the minimised sequence, not the
                        // original: for a shrunk counterexample the two can differ
                        // (e.g. a different failing step), and the developer acts on
                        // the minimal one. Falls back to the original when nothing shrank.
                        failure: $shrunkFailure ?? $result->failure,
                        skips: $skips,
                    )),
                    attributes: $runAttributes,
                );
            }

            foreach ($labels as $label) {
                $classifications[$label] = ($classifications[$label] ?? 0) + 1;
            }

            ++$checks;
        }

        $this->warnOnExcessiveSkips($info->name, $skips, $attempts);
        $this->reportClassifications($info->name, $classifications, $checks);

        $requirements = Classify::flushRequirements();

        $violation = $this->coverageViolation($info->name, $requirements, $classifications, $checks);

        if ($violation instanceof CoverageViolationException) {
            return new TestResult(
                info: $info,
                status: Status::Failed,
                failure: $violation,
                attributes: $runAttributes,
            );
        }

        return new TestResult(
            info: $info,
            status: Status::Passed,
            attributes: $runAttributes,
        );
    }

    /**
     * Check the {@see Classify::cover()} requirements against the label counts
     * of the passing runs; every run passed, but an under-covered label means
     * the pass is (partially) vacuous and must fail. The successful-run loop
     * guarantees the denominator is always positive.
     *
     * @param array<string, float> $requirements
     * @param array<string, int> $classifications
     */
    private function coverageViolation(
        string $name,
        array $requirements,
        array $classifications,
        int $checks,
    ): ?CoverageViolationException {
        if ($requirements === []) {
            return null;
        }

        $unmet = [];
        foreach ($requirements as $label => $minPercent) {
            $count = $classifications[$label] ?? 0;
            $percent = ((float) $count / (float) $checks) * 100.0;

            if ($percent < $minPercent) {
                $unmet[] = sprintf(
                    '"%s" %.1f%% < required %.1f%% (%d/%d)',
                    $label,
                    $percent,
                    $minPercent,
                    $count,
                    $checks,
                );
            }
        }

        if ($unmet === []) {
            return null;
        }

        return new CoverageViolationException(sprintf(
            'Property "%s" coverage not met: %s',
            $name,
            implode('; ', $unmet),
        ));
    }

    /**
     * The `PROPERTY_RUNS` environment variable overrides the attribute's run
     * count (handy for dialing runs up in CI). It must be a positive integer.
     */
    private function resolveRuns(int $runs): int
    {
        $env = getenv('PROPERTY_RUNS');

        if ($env === false || $env === '') {
            return $runs;
        }

        if (preg_match('/^\d+$/', $env) !== 1 || (int) $env < 1) {
            throw new \InvalidArgumentException(sprintf('PROPERTY_RUNS must be a positive integer, got "%s"', $env));
        }

        return (int) $env;
    }

    private function resolveMaxDiscards(?int $maxDiscards, int $runs): int
    {
        if ($maxDiscards !== null) {
            return $maxDiscards;
        }

        return $runs > intdiv(PHP_INT_MAX, 10) ? PHP_INT_MAX : $runs * 10;
    }

    /**
     * `PROPERTY_VERBOSE` (any value except '' and '0') logs every run's
     * generated arguments — for debugging a property whose failure depends on
     * inputs you cannot see in the counterexample alone.
     */
    private function resolveVerbose(): bool
    {
        $env = getenv('PROPERTY_VERBOSE');

        return !in_array($env, [false, '', '0'], true);
    }

    /**
     * One compact `name=value` list per run for verbose logging (mirrors the
     * counterexample rendering of {@see PropertyViolationException}).
     *
     * @param array<string, mixed> $arguments
     */
    private function formatArguments(array $arguments): string
    {
        $pairs = array_map(
            static fn(mixed $value, mixed $name): string => $name . '=' . ValueRenderer::render($value),
            $arguments,
            array_keys($arguments),
        );

        return implode(', ', $pairs);
    }

    /**
     * The attribute seed wins; otherwise `PROPERTY_SEED` fixes the seed for the
     * whole suite (handy for replaying a CI failure); otherwise a random seed is
     * drawn. `PROPERTY_SEED`, when set, must be an integer.
     */
    private function resolveSeed(?int $attributeSeed): int
    {
        if ($attributeSeed !== null) {
            return $attributeSeed;
        }

        $env = getenv('PROPERTY_SEED');

        if ($env === false || $env === '') {
            return random_int(0, PHP_INT_MAX);
        }

        if (preg_match('/^-?\d+$/', $env) !== 1) {
            throw new \InvalidArgumentException(sprintf('PROPERTY_SEED must be an integer, got "%s"', $env));
        }

        return (int) $env;
    }

    /**
     * @param array<string, ArbitraryInterface> $generators
     * @param list<string> $parameterNames
     * @return array<string, Shrinkable>
     */
    private function generate(array $generators, array $parameterNames, Random $random): array
    {
        return array_combine(
            $parameterNames,
            array_map(
                static fn(string $name): Shrinkable => $generators[$name]->generate($random),
                $parameterNames,
            ),
        );
    }

    /**
     * @param array<string, Shrinkable> $trees
     * @return array<string, mixed>
     */
    private function values(array $trees): array
    {
        return array_map(
            static fn(Shrinkable $tree): mixed => $tree->value,
            $trees,
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
     * Runs the fixed examples (if any) before the random inputs, short-circuiting
     * on the first failure — a pinned example is already minimal, so it is not
     * shrunk. Returns the failing {@see TestResult}, or null when all pass.
     *
     * @param callable(TestInfo): TestResult $next
     */
    private function runExamples(\ReflectionMethod $testMethod, TestInfo $info, callable $next, Property $property, int $seed): ?TestResult
    {
        $index = 0;
        // Examples may call Gen::draw(); their draws come from a dedicated
        // deterministic stream so the random phase's sequence is untouched.
        $random = new Random($seed);

        foreach ($this->resolveExamples($testMethod, $info, $property) as $arguments) {
            Classify::beginRun();
            DrawContext::arm($random);
            $result = $next($info->with(arguments: $arguments));
            DrawContext::disarm();
            Classify::flushRun();

            if (!$result->failure instanceof AssumptionSkipped && $result->status->isFailure()) {
                return new TestResult(
                    info: $info,
                    status: Status::Failed,
                    failure: new ExampleViolationException($index, $arguments, $result->failure),
                    // Keep the failing run's attributes (e.g. codecov's
                    // CoverageResult) on the aggregate result — see runProperty().
                    attributes: $result->attributes,
                );
            }

            ++$index;
        }

        return null;
    }

    /**
     * Resolves the property's explicit examples: the attribute's `examples`
     * method name, or the `<testMethod>Examples` convention when that method
     * exists. Each yielded array becomes a list of positional arguments.
     *
     * @return list<list<mixed>>
     */
    private function resolveExamples(\ReflectionMethod $testMethod, TestInfo $info, Property $property): array
    {
        $methodName = $property->examples ?? $testMethod->getName() . 'Examples';
        $class = $testMethod->getDeclaringClass();

        if (!$class->hasMethod($methodName)) {
            if ($property->examples !== null) {
                throw new \InvalidArgumentException(sprintf(
                    'Property "%s" references examples method "%s" which does not exist on %s',
                    $testMethod->getName(),
                    $methodName,
                    $class->getName(),
                ));
            }

            return [];
        }

        $method = $class->getMethod($methodName);

        /** @var mixed $examples */
        $examples = $method->isStatic()
            ? $method->getClosure()()
            : $method->getClosure($info->caseInfo->instance?->getInstance())();

        if (!is_iterable($examples)) {
            throw new \InvalidArgumentException(sprintf(
                'Examples method "%s" must return an iterable, got %s',
                $methodName,
                get_debug_type($examples),
            ));
        }

        $expectedArity = count($testMethod->getParameters());
        $typed = [];

        foreach ($examples as $example) {
            if (!is_array($example)) {
                throw new \InvalidArgumentException(sprintf(
                    'Examples method "%s" must yield arrays of positional arguments, got %s',
                    $methodName,
                    get_debug_type($example),
                ));
            }

            $arguments = array_values($example);

            if (count($arguments) !== $expectedArity) {
                throw new \InvalidArgumentException(sprintf(
                    'Example #%d for "%s" has %d argument(s), but the property takes %d',
                    count($typed),
                    $testMethod->getName(),
                    count($arguments),
                    $expectedArity,
                ));
            }

            $typed[] = $arguments;
        }

        return $typed;
    }

    /**
     * Greedy per-parameter descent through each parameter's shrink tree: try the
     * candidates of one parameter's current node, accept the first that still
     * fails (descending into that candidate's subtree), and keep iterating until
     * a full pass produces no improvement. Termination is guaranteed by the
     * {@see ArbitraryInterface} contract: every branch of a shrink tree is finite.
     *
     * In-body draws shrink through a replay tape walked like extra parameters:
     * each trial re-runs the body with the modified tape, and the draws that
     * trial actually used (the replayed prefix plus a freshly generated suffix,
     * when control flow changed) become the tape of the next round on
     * acceptance. Because an accepted candidate can regrow the tape with fresh
     * trees, the finite-tree argument no longer bounds the descent — with a
     * non-empty tape the accepted steps are additionally capped by
     * {@see self::MAX_DRAW_SHRINK_STEPS}.
     *
     * @param array<string, Shrinkable> $trees The failing arguments' shrink trees.
     * @param list<Shrinkable> $tape The failing run's recorded in-body draws.
     * @param callable(TestInfo): TestResult $next
     * @param ?int $maxShrinks Cap on accepted shrink steps; null means no cap, 0 disables shrinking.
     * @return array{0: array<string, mixed>, 1: array<string, mixed>, 2: int, 3: ?\Throwable} The
     *         minimised arguments, the minimised draws (as `draw#N` pseudo-arguments), the number
     *         of accepted shrink steps, and the failure of the last accepted candidate (null when
     *         nothing shrank).
     */
    private function shrink(
        TestInfo $info,
        callable $next,
        array $trees,
        array $tape,
        Random $random,
        ?int $maxShrinks,
    ): array {
        $current = $trees;
        $currentTape = $tape;
        $steps = 0;
        $acceptedFailure = null;

        do {
            $improved = false;

            foreach (array_keys($current) as $name) {
                // Stop before accepting any further candidate once the cap is hit.
                // Checking here (before the per-parameter search) makes maxShrinks=0
                // return the original counterexample with zero accepted steps.
                if ($this->capReached($maxShrinks, $currentTape, $steps)) {
                    return [$this->values($current), $this->drawArguments($currentTape), $steps, $acceptedFailure];
                }

                foreach ($current[$name]->shrinks() as $candidate) {
                    // A candidate whose value equals the current one (possible under a
                    // non-injective map) makes no progress; skip it and its subtree.
                    if ($candidate->value === $current[$name]->value) {
                        continue;
                    }

                    // Replace one parameter in place: array_replace keeps $current's
                    // key order, so array_values() still maps each value to the right
                    // parameter. Array union ([$name => ...] + $current) would move
                    // $name to the front and scramble non-leading parameters.
                    $trial = array_replace($current, [$name => $candidate]);
                    [$trialResult, $recorded] = $this->trial($info, $next, $trial, $currentTape, $random);

                    if ($this->isFailingResult($trialResult)) {
                        $current = $trial;
                        $currentTape = $recorded;
                        $acceptedFailure = $trialResult->failure;
                        ++$steps;
                        $improved = true;

                        break;
                    }
                }
            }

            // Walk the tape positions like extra parameters. The bound is re-read
            // every iteration (a while, NOT a hoisted-count for): an accepted
            // candidate truncates the tape when the body draws fewer values under
            // the smaller prefix.
            $position = 0;

            while ($position < count($currentTape)) {
                if ($this->capReached($maxShrinks, $currentTape, $steps)) {
                    return [$this->values($current), $this->drawArguments($currentTape), $steps, $acceptedFailure];
                }

                foreach ($currentTape[$position]->shrinks() as $candidate) {
                    if ($candidate->value === $currentTape[$position]->value) {
                        continue;
                    }

                    $trialTape = array_replace($currentTape, [$position => $candidate]);
                    [$trialResult, $recorded] = $this->trial($info, $next, $current, $trialTape, $random);

                    if ($this->isFailingResult($trialResult)) {
                        $currentTape = $recorded;
                        $acceptedFailure = $trialResult->failure;
                        ++$steps;
                        $improved = true;

                        break;
                    }
                }

                ++$position;
            }
        } while ($improved);

        return [$this->values($current), $this->drawArguments($currentTape), $steps, $acceptedFailure];
    }

    /**
     * One shrink trial: run the body with the candidate arguments while the
     * draw context replays $tape, and report the result together with the
     * draws the body actually used.
     *
     * @param array<string, Shrinkable> $trees
     * @param list<Shrinkable> $tape
     * @param callable(TestInfo): TestResult $next
     * @return array{0: TestResult, 1: list<Shrinkable>}
     */
    private function trial(TestInfo $info, callable $next, array $trees, array $tape, Random $random): array
    {
        DrawContext::arm($random, $tape);
        $result = $next($info->with(arguments: array_values($this->values($trees))));

        return [$result, DrawContext::disarm()];
    }

    /**
     * Whether shrinking must stop before accepting another candidate. The cap
     * is re-derived from the CURRENT tape: a body that drew nothing on the
     * original run can still start drawing once a shrunk parameter changes its
     * control flow, and from that point the draw cap must engage.
     *
     * @param list<Shrinkable> $tape
     */
    private function capReached(?int $maxShrinks, array $tape, int $steps): bool
    {
        $cap = $maxShrinks ?? ($tape === [] ? null : self::MAX_DRAW_SHRINK_STEPS);

        return $cap !== null && $steps >= $cap;
    }

    /**
     * Render in-body draws as pseudo-arguments (`draw#1`, `draw#2`, ...) for
     * counterexample reporting. `#` cannot occur in a PHP parameter name, so
     * the keys never collide with real parameters.
     *
     * @param list<Shrinkable> $draws
     * @return array<string, mixed>
     */
    private function drawArguments(array $draws): array
    {
        return array_combine(
            array_map(static fn(int $index): string => 'draw#' . ($index + 1), array_keys($draws)),
            array_map(static fn(Shrinkable $draw): mixed => $draw->value, $draws),
        );
    }

    /**
     * A run counts as a shrink failure when it failed for a real reason — a
     * discarded run ({@see AssumptionSkipped}) is not a smaller counterexample.
     */
    private function isFailingResult(TestResult $result): bool
    {
        if ($result->failure instanceof AssumptionSkipped) {
            return false;
        }

        return $result->status->isFailure();
    }

    /**
     * Print the share of (passing) runs that hit each {@see Classify} label.
     *
     * @param array<string, int> $classifications
     */
    private function reportClassifications(string $name, array $classifications, int $checks): void
    {
        if ($classifications === [] || $checks <= 0) {
            return;
        }

        arsort($classifications);

        $parts = [];
        foreach ($classifications as $label => $count) {
            $parts[] = sprintf(
                '%s %d%% (%d/%d)',
                $label,
                (int) round(((float) $count / (float) $checks) * 100.0),
                $count,
                $checks,
            );
        }

        $this->messenger->log(
            Messenger::CHANNEL_STDOUT,
            sprintf('Property "%s" distribution: %s', $name, implode(', ', $parts)),
            Level::Info,
        );
    }

    private function warnOnExcessiveSkips(string $name, int $skips, int $attempts): void
    {
        if ($attempts <= 0 || ($skips / $attempts) <= self::SKIP_RATE_WARNING_THRESHOLD) {
            return;
        }

        $this->messenger->log(
            Messenger::CHANNEL_STDERR,
            sprintf(
                'Property "%s" discarded %d of %d attempt(s) (%d%%); consider narrowing the generators',
                $name,
                $skips,
                $attempts,
                (int) round(((float) $skips / (float) $attempts) * 100.0),
            ),
            Level::Warning,
        );
    }
}
