# AGENTS.md — property-testing

Guidance for AI agents working on this package. Read before changing code.

## What this is

This package provides property-based testing for the Testo testing framework.
The public API lives in the `Rasuvaeff\PropertyTesting` namespace and consists
of: the `#[Property]` attribute, the `Gen` static facade of generators
(implementing `ArbitraryInterface`), the `Assume::that()` discard helper, the
`PropertyViolationException`/`CounterExample` failure carriers, and the
self-registering `PropertyInterceptor` that drives the run/falsify/shrink loop.

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
4. **Preserve shrinking termination.** Shrinking accepts only candidates that
   differ from the current value and still fail; candidates equal to the
   current value must be skipped to avoid an infinite loop.
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
- Generators are value objects (`final readonly`); `Random` is the only mutable
  type (it advances its engine on each draw).
- `Gen::filter()` retries up to 100 times then yields the last value; prefer
  `Assume::that()` in the property body when the rejection rate is high.
- `yield from` inside a generator that already `yield`ed causes integer-key
  collisions (later values overwrite earlier ones). Spread inner shrink
  candidates with an explicit `foreach` + `yield`, not `yield from`.
- Shrinking is greedy per-parameter and best-effort minimal, not provably
  minimal (halving toward zero/empty; no exhaustive search).
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
