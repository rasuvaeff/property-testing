# AGENTS.md — property-testing

Guidance for AI agents working on this package. Read before changing code.

## What this is

This package provides property-based testing for the Testo testing framework.
The public API lives in the `Rasuvaeff\PropertyTesting` namespace and consists
of: the `#[Property]` attribute, the `Gen` static facade of generators
(implementing `ArbitraryInterface`), the `Shrinkable` value/lazy-shrink-tree
node (integrated shrinking: `generate()` returns the value plus its shrink
tree; there is no `shrink(mixed)` method), the `Assume::that()` discard helper,
the `PropertyViolationException`/`CounterExample` failure carriers, and the
self-registering `PropertyInterceptor` that drives the run/falsify/shrink loop.

Stateful / model-based testing lives in the `Rasuvaeff\PropertyTesting\StateMachine`
namespace: the `Command` interface (`preCondition`/`nextState`/`run`/`postCondition`,
`\Stringable` label), the `CommandSequence` value, the `StateMachine::check()`
runner, and `PostconditionViolation`. `Gen::commands()` returns the
`CommandSequenceArbitrary` that generates and shrinks command sequences; it plugs
into the same `#[Property]` machinery as every other arbitrary.

The `2.3.0` additions: domain arbitraries on `Gen` (`ipv4`/`email`/`url`/`json`/
`jsonString`, and `regex`/`stringMatching` compiled by the internal
`RegexCompiler` — a PCRE-subset recursive-descent compiler to combinators);
explicit examples (`#[Property(examples: …)]` / `<testMethod>Examples`, failing
via `ExampleViolationException`); and opt-in seed persistence/replay via the
internal `SeedStorage` (`PROPERTY_DB`).

The `2.4.0` addition: in-body dependent draw — `Gen::draw($arb)` inside the
property body, backed by the internal `DrawContext` replay tape (fast-check's
`gen()` model layered over the tree shrink model). Draws are recorded as
`Shrinkable`s, shrunk like extra parameters, and replayed by position on every
shrink trial; counterexamples report them as `draw#N` pseudo-arguments.

It is a Testo plugin, not a standalone runner. It depends on Testo's stable
`@api` surfaces: `TestRunInterceptor`, `TestInfo`, `TestResult`, `Messenger`,
the `Interceptable`/`FallbackInterceptor`/`InterceptorOptions` attributes.

## Golden rules

1. **Verification is mandatory.** Never claim "done" without a fresh green
   `composer build`. "Should work" does not count.
2. **No suppressions.** No `@psalm-suppress`, no baseline. Fix the root cause.
3. **Preserve branch identity in the loop.** `#[Property]` must invoke the test
   exactly once per generated input; a discarded run (`Assume::that(false)`)
   is neither a failure nor a successful check.
4. **Preserve shrinking termination.** Every branch of a `Shrinkable` tree
   must be finite and no candidate may equal its parent value (each builder
   guarantees a strictly decreasing measure: distance to target, length,
   non-'a' count, list index). The interceptor additionally skips candidates
   whose value equals the current one (possible under a non-injective map).
5. **Preserve the public contract.** Update README + tests with any API change.

## Commands

No PHP/Composer on the host — run in Docker via the `composer:2` image.

```bash
docker run --rm -v "$PWD":/app -w /app composer:2 composer build
docker run --rm -v "$PWD":/app -w /app composer:2 composer cs:fix
docker run --rm -v "$PWD":/app -w /app composer:2 composer psalm
docker run --rm -v "$PWD":/app -w /app composer:2 composer test
docker run --rm -v "$PWD":/app -w /app composer:2 composer release-check
```

Or with Make:

```bash
make build
make cs-fix
make psalm
make test
make test-coverage
make mutation
make release-check
```

`composer.lock` is gitignored (library).
`make test-coverage` and `make mutation` bootstrap `pcov` inside the
`composer:2` container because the base image has no coverage driver.

## Invariants & gotchas

- Attribute arguments are constant expressions in PHP. Generators CANNOT be
  passed inline to `#[Property]`; they must come from a named method returning
  `array<string, ArbitraryInterface>` keyed by parameter name.
- `Random` uses an object-scoped `\Random\Randomizer` (MT19937 engine), NOT PHP's
  global `mt_srand`/`mt_rand`. Two `Random` instances with the same seed produce
  identical sequences regardless of intervening random calls — this is what makes
  reported seeds reproducible inside a busy test runner.
- Generators are value objects (`final readonly`). Three types hold mutable
  state: `Random` (advances its engine on each draw), `Classify` (a
  process-local static buffer of the current run's distribution labels) and
  `DrawContext` (the process-local replay tape for `Gen::draw()`). `Classify`
  is the body↔runner channel for `classify`/`collect`; the interceptor clears
  it via `beginRun()` and drains it via `flushRun()` each run, so it is never
  shared concurrently (property runs are sequential). `DrawContext` follows
  the same discipline: `arm()` before every body execution, `disarm()` after
  (plus a defensive disarm at the start of each property).
- In-body draw shrinking is replay-tape-based and intentionally does NOT
  re-validate replayed nodes: a shrunk parameter can change control flow so a
  draw position meets a different (narrower) arbitrary, and the tape still
  serves the recorded node as-is (fast-check `gen()` model). Draws past the
  tape's end generate anew from the run's `Random`; an accepted trial's
  actually-used draws become the next tape (this is what truncates unreachable
  tails). Because a regrown tape carries fresh trees, the finite-tree
  termination argument alone does not bound the descent — the interceptor caps
  accepted steps via `MAX_DRAW_SHRINK_STEPS` whenever the tape is non-empty.
  Do not remove the cap or add re-validation to the replay.
- `Classify` carries a second static: coverage requirements from `cover()`,
  scoped per PROPERTY (not per run). The interceptor drains them once after
  the run loop via `flushRequirements()` and defensively before it — a
  falsified property returns early and would otherwise leak its requirements
  into the next one.
- `Gen::filter()` retries up to 100 times then yields the last value; prefer
  `Assume::that()` in the property body when the rejection rate is high. For
  dependent domains use `Gen::flatMap()` instead of filtering.
- `yield from` inside a generator that already `yield`ed causes integer-key
  collisions (later values overwrite earlier ones). Spread inner shrink
  candidates with an explicit `foreach` + `yield`, not `yield from`.
- Shrink trees are built at generation time: composite arbitraries keep their
  components as `Shrinkable`s (not raw values) so transformed elements shrink
  through their own trees. `FlatMappedArbitrary` captures one extra seed at
  generate() time to regenerate the dependent value deterministically when the
  source shrinks — do not replace it with ambient randomness.
- `Shrinkable::shrinks()` re-invokes its closure on every call; children must
  be re-derivable (pure closures over immutable state).
- Stateful validity is enforced at RUN time, not shrink time.
  `CommandSequenceArbitrary` generates valid-by-construction sequences (each step
  respects `preCondition` against the running model) but shrinks by pure list
  drop/element-shrink WITHOUT re-validating; `StateMachine::check()` skips any
  command whose precondition a dropped/simplified step invalidated. Do not add
  precondition re-validation to the arbitrary — the skip-on-replay is the
  contract (fast-check model), and it keeps shrink candidates cheap and sound.
- `CommandSequence` and every `Command` are `\Stringable` so counterexamples
  render as a readable trace; the `PropertyViolationException`/interceptor
  renderers have a `\Stringable` arm before their `default` (class-name) arm.
- The interceptor reports the SHRUNK run's failure (`shrink()` returns the last
  accepted candidate's throwable), not the original draw's — so the `Failure:`
  line matches the `Shrunk:` arguments. Keep these in sync.
- **Aggregate results must carry per-run `TestResult` attributes.** Downstream
  interceptors attach per-run attributes to each `$next()` result — Testo
  codecov's `CoverageResult` among them (its interceptor is innermost, order
  `PHP_INT_MAX`). Every `TestResult` the interceptor constructs (pass,
  falsified, coverage violation, failing example) must pass the merged
  `$runAttributes` along; a bare `new TestResult(info, status)` makes property
  tests vanish from per-test coverage and Infection then never runs them
  against mutants.
- Shrinking is a greedy per-parameter tree descent and best-effort minimal,
  not provably minimal (no exhaustive search). For monotone predicates the
  int ladder is an exact binary search.
- Tests obtain trees only via generation: `tests/Support/Trees.php` scans
  sequential seeds (`generateWhere`) for a node with the wanted value, then
  asserts on `childValues`/`valuesToDepth`/`descendWhile`.
- Code: `declare(strict_types=1)`, `final readonly class`, `#[\Override]`,
  explicit types.
- `examples/` is part of the public contract: keep scripts runnable and update
  `examples/README.md` when example usage changes.
- **CI workflows are SHA-pinned.** Every `uses:` in `.github/workflows/*.yml`
  references a 40-char commit SHA with a `# vN` trailing comment
  (e.g. `actions/checkout@<sha> # v4`). Never revert to floating `@vN` tags.
  Updates go through Dependabot, which bumps the SHA and preserves the comment.
  Workflows also carry `permissions: { contents: read }` at workflow level and
  `persist-credentials: false` on every `actions/checkout` step. Verify with
  `zizmor --persona=auditor .github/` — must report no `unpinned-uses`,
  `excessive-permissions`, or `artipacked` findings.

## When you finish

- Update `README.md` (and `examples/` if usage changed); update `CHANGELOG.md`
  when releasing.
- Re-run `composer build`; if the change affects public API or release safety,
  also run `make release-check`. Paste the output.
