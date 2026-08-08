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
self-registering `PropertyInterceptor` that adapts a property test to the
engine.

Since the stage-D runner split (evolution plan) the run/falsify/shrink loop
lives in the framework-agnostic `Rasuvaeff\PropertyTesting\Runner` namespace:
`PropertyRunner` (examples → corpus replay → random phase → shrink),
`PropertyDefinition`/`PropertyConfig` (fully resolved input — no attribute, no
reflection, no environment), the `TrialExecutor`/`TrialOutcome` seam
(`CallableTrialExecutor` for standalone harnesses), the `Corpus` interface, and
the closed `PropertyResult` hierarchy (`Passed`, `Falsified`, `GaveUp`,
`CoverageFailed`, `DeadlineExceeded`, `TimeBudgetExceeded`, `GenerationFailed`,
`ExampleFailed`, `RegressionFailed`) whose failing members carry the package's
established exception types, constructed by the runner. `PropertyInterceptor`
is the Testo adapter: it resolves reflection conventions and env overrides into
a definition, executes bodies through the attribute-aggregating
`TestoTrialExecutor`, and maps the structured result to one Testo `TestResult`
(printing the distribution report / discard warning via `Messenger`). The
`Runner` namespace is `@internal` until property-testing-core 1.0 ships it as
`@api`. Engine directories (`src/Runner`, `src/Event`, `src/Arbitrary`,
`src/StateMachine`, and everything in `src/Internal` except the interceptor,
`TestoTrialExecutor` and `VerboseListener`) must stay free of `Testo\`
references — that boundary is the split's exit criterion.

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
via `ExampleViolationException`); and opt-in failure persistence/replay via the
internal `CorpusStorage` (`PROPERTY_DB`).

The `2.8.0` addition: the regression corpus — `CorpusStorage` (the filesystem
`Runner\Corpus` implementation) keeps several past failures per property
(`CorpusEntry`, encoded by the internal `ValueCodec`), preferring the minimised
input as data over the bare seed, and the runner replays them all before the
random phase (`RegressionViolationException` for a values entry).

The `2.4.0` addition: in-body dependent draw — `Gen::draw($arb)` inside the
property body, backed by the internal `DrawContext` replay tape (fast-check's
`gen()` model layered over the tree shrink model). Draws are recorded as
`Shrinkable`s, shrunk like extra parameters, and replayed by position on every
shrink trial; counterexamples report them as `draw#N` pseudo-arguments.

It ships as a Testo plugin; the engine itself is framework-free and drivable
directly (see `examples/standalone_runner.php`). Only the adapter classes —
`Property`, `PropertyInterceptor`, `TestoTrialExecutor`, `VerboseListener` —
depend on Testo's stable `@api` surfaces: `TestRunInterceptor`, `TestInfo`,
`TestResult`, `Messenger`, the
`Interceptable`/`FallbackInterceptor`/`InterceptorOptions` attributes.

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
   non-'a' count, list index). The runner additionally skips candidates
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

## Environment contract

The exact semantics of every supported variable. This resolution belongs to
the framework adapter (`PropertyInterceptor` resolves the table below into a
`PropertyConfig`/`Corpus`; `PropertyRunner` never reads the process
environment); after the physical split every adapter must reproduce this table
verbatim. Each row is pinned by tests in `PropertyInterceptorTest`.

| Variable | Read when | Accepts | Effect | Invalid value |
|---|---|---|---|---|
| `PROPERTY_RUNS` | Always (`false`/`''` = unset) | `/^\d+\z/`, `>= 1` | Overrides every property's run count, including the attribute's | `InvalidArgumentException` |
| `PROPERTY_SEED` | Only when the attribute omits `seed` (attribute wins) | `/^-?\d+\z/` | Seeds every unseeded property; unset means `random_int(0, PHP_INT_MAX)` per property | `InvalidArgumentException` |
| `PROPERTY_VERBOSE` | Always | Any value except `''` and `'0'` enables | Logs every run's arguments/draws and each accepted shrink step to stdout | n/a (falsy values disable) |
| `PROPERTY_DB` | Always (`false`/`''` = off, nothing written) | Directory path (created on demand) | Enables the regression corpus: record on falsification, replay before the random phase, prune on green replay. An attribute `seed` disables replay for that property | n/a |

`maxDiscards` has no env override: unset means `runs * 10`, saturating to
`PHP_INT_MAX` when `runs > PHP_INT_MAX / 10`.

## Invariants & gotchas

- **The event model (`Event\*`, `PropertyListener`) is `@internal` until the
  core split ships it as `@api`.** Events carry engine data only — property id,
  seed, attempts, arguments, labels, elapsed time, failures, counterexamples;
  Testo types never appear in an event. A listener exception aborts the run
  (deliberately not caught in `emit()`); the built-in `VerboseListener` is the
  one hardened exception — it swallows its own errors so a trace bug cannot
  fail every `PROPERTY_VERBOSE` consumer. The `PROPERTY_VERBOSE` line formats
  now live in `VerboseListener` and are pinned by `GoldenMessagesTest`; the
  exact per-outcome event sequences are pinned by `EventOrderTest`. An extra,
  missing or reordered event is an observable engine change — update those
  characterizations in the same commit, never loosen them.

- Attribute arguments are constant expressions in PHP. Generators CANNOT be
  passed inline to `#[Property]`; they must come from a named method returning
  `array<string, ArbitraryInterface>` keyed by parameter name.
- Generators/examples methods must be **public static** (public when the body
  needs `$this`): their only call site is this package's reflection, so
  rector's `RemoveUnusedPrivateMethodRector` (deadCode set) deletes private
  ones. Public methods are safe — no dead-code rule touches them, and Testo
  does not treat non-void-returning methods as tests.
- `Random` uses an object-scoped `\Random\Randomizer` (MT19937 engine), NOT PHP's
  global `mt_srand`/`mt_rand`. Two `Random` instances with the same seed produce
  identical sequences regardless of intervening random calls — this is what makes
  reported seeds reproducible inside a busy test runner.
- Generators are value objects (`final readonly`). Three types hold mutable
  state: `Random` (advances its engine on each draw), `Classify` (a
  process-local static buffer of the current run's distribution labels) and
  `DrawContext` (the process-local replay tape for `Gen::draw()`). `Classify`
  is the body↔runner channel for `classify`/`collect`; the runner clears
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
  termination argument alone does not bound the descent — the runner caps
  accepted steps via `MAX_DRAW_SHRINK_STEPS` whenever the tape is non-empty.
  Do not remove the cap or add re-validation to the replay.
- **The regression corpus must never replay a different input than it recorded.**
  Three guards enforce that, and removing any of them turns the corpus into a
  source of false green runs:
  - a counterexample carrying `draw#N` pseudo-arguments (in-body `Gen::draw()`)
    is stored as a SEED, never as values — replaying the named parameters alone
    would let the body draw fresh values. Feeding stored values into
    `DrawContext` would need a tape-replay corpus format, which does not exist;
  - `CorpusStorage::recall()` drops a values entry whose argument names are not
    exactly the property's current parameters, and orders the recalled values by
    the reflection parameter list (never by insertion order);
  - `CorpusStorage::SEQUENCE_EPOCH` fences seed entries off. Bump it in any
    release that shifts the generated sequence for a given seed (new boundary
    bias, changed draw order, a rewritten arbitrary) — otherwise an old seed
    replays a different input while claiming to be a regression. Values entries
    carry the input and are deliberately exempt.
- **CorpusStorage writes are atomic and serialised by a cross-process flock.**
  `remember()`/`prune()` do read-modify-write; without serialisation,
  `infection --threads=max` or parallel CI jobs sharing `PROPERTY_DB` would
  each read the same state and the second commit would silently lose the first.
  `write()` goes through a temp file + `rename()` so a process killed mid-write
  (OOM, signal) leaves the previous file intact rather than a truncated JSON
  the next `recall()` would silently drop. The lock file (`.json.lock` next to
  the corpus file) is reused across calls and keyed by property id; do not
  remove it or switch `write()` back to a bare `file_put_contents()`.
- `ValueCodec` sends EVERY float through a tagged envelope, as text.
  `json_encode()` renders an integral float as an integer literal (`0.0` -> `0`),
  so an unenveloped float decodes back as an int — the package's own property
  test `encodedValuesSurviveJsonTransport` catches this. Do not "optimise" finite
  floats back to raw JSON numbers.
- Psalm 6.16 crashes (`TLiteralFloat`: "unexpected NAN value was coerced to
  string") when it must infer a literal type for the `NAN` constant in `src/`.
  `ValueCodec::decodeFloat()` computes it with `fdiv(0.0, 0.0)` for that reason;
  re-check before replacing it with `NAN`.
- `Classify` carries a second static: coverage requirements from `cover()`,
  scoped per PROPERTY (not per run). The runner drains them via
  `flushRequirements()` on every exit path of the random phase (including
  falsification, since stage D) and defensively at the start of `run()` — an
  aborted property (e.g. a throwing listener) must not leak its requirements
  into the next one.
- `Gen::filter()` retries up to 100 times then throws `GenerationExhausted`
  (never yields a value that fails the predicate); the runner catches it at
  the generation step and reports a clean failure (`GenerationFailed`). Prefer `Assume::that()` in the
  property body when the rejection rate is high, or `Gen::flatMap()` for
  dependent domains instead of filtering.
- Sized collections guarantee their minimum: `dictOf`/`uniqueArrayOf` (distinct
  keys/elements) and `commands` (applicable steps) throw `GenerationExhausted`
  when the drawn minimum is unreachable, never returning a too-small value.
- `Property::$runs` means successful checks. Discarded attempts do not consume
  it; they are retried until `maxDiscards` (default `runs * 10`) is exceeded,
  then the property fails with a structured `GaveUpException`.
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
- The runner reports the SHRUNK run's failure (`shrink()` returns the last
  accepted candidate's throwable), not the original draw's — so the `Failure:`
  line matches the `Shrunk:` arguments. Keep these in sync.
- **Aggregate results must carry per-run `TestResult` attributes.** Downstream
  interceptors attach per-run attributes to each `$next()` result — Testo
  codecov's `CoverageResult` among them (its interceptor is innermost, order
  `PHP_INT_MAX`). `TestoTrialExecutor` merges every executed run's attributes
  (last write per key wins) and the interceptor puts that aggregate on the one
  `TestResult` it returns; dropping that merge makes property tests vanish
  from per-test coverage and Infection then never runs them against mutants.
  Since the runner split the merge covers shrink trials and passing examples
  too — strictly more coverage data than 2.8 kept.
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

- Update `README.md` AND `README.ru.md` — the README is bilingual, every
  change lands in both files in the same commit (and `examples/` if usage
  changed); update `CHANGELOG.md` when releasing.
- Re-run `composer build`; if the change affects public API or release safety,
  also run `make release-check`. Paste the output.
