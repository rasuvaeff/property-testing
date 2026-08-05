---
title: "Cookbook: CI recipes"
---

# Cookbook: CI recipes

Three small adjustments that matter once property tests run in CI rather
than on a developer's machine: dialing run counts up without editing every
attribute, keeping the regression corpus out of version control, and
bounding how long a pathological input is allowed to run.

## Dial `runs` up for CI without touching test code

Attribute-level `runs:` is what a developer iterates against locally — low
enough to stay fast in a tight edit loop. CI can afford (and benefits from)
far more random inputs per property without a source change:

```bash
docker run --rm -v "$PWD":/app -w /app -e PROPERTY_RUNS=2000 \
  composer:2 vendor/bin/testo
```

`PROPERTY_RUNS` unconditionally overrides every property's `runs` count for
that invocation — including properties that pinned their own value on the
attribute. There's no floor logic: if a property deliberately sets a low
`runs:` because its generator is expensive, a blanket `PROPERTY_RUNS=2000`
in CI applies to it too. Keep genuinely slow properties genuinely slow-aware
some other way (a narrower generator domain, `budgetMs` below) rather than
relying on the env var to leave them alone.

## Gitignore the regression corpus directory

`PROPERTY_DB=<dir>` turns on the [regression corpus](/en/regression-corpus):
every falsified property persists its failure as a small JSON file, replayed
before the random phase on the next run. That's exactly the behavior you
want *within* a CI run or a local debugging session, and exactly the
behavior you don't want leaking into git — a committed corpus directory
would pin every contributor's local failures into the shared history and
make `PROPERTY_DB` runs non-reproducible across machines.

```
# .gitignore
/.property-db/
```

Point `PROPERTY_DB` at that same path in CI:

```bash
docker run --rm -v "$PWD":/app -w /app -e PROPERTY_DB=.property-db \
  composer:2 vendor/bin/testo
```

If you want failures to persist *across* CI runs (catch a flaky-looking
property that fails once every few thousand runs before it fully
regresses), cache the directory between jobs via your CI provider's cache
action, keyed on something stable — but keep it out of the repository
itself.

## Bound pathological inputs with `budgetMs`

`PROPERTY_RUNS=2000` in CI has an unbounded downside: a generator that
occasionally produces a catastrophically slow input (deep recursion, a
regex the property matches against) can make the whole random phase run far
longer than any one CI job budget allows, without a single assertion ever
failing.

```php
#[Property(runs: 2000, budgetMs: 30_000)]
public function parsesWithinBudget(string $input): void
{
    // ...
}
```

`budgetMs` caps the entire random phase; overrunning it before `runs`
successful checks complete throws `TimeBudgetExceededException` with the
completed/required counts — a clear CI failure instead of a job that times
out with no diagnostic. For a single pathological *input* rather than the
whole phase, `timeoutMs` is the narrower tool — see
[Deadlines and time budgets](/en/controlling-runs/deadlines) for the
distinction and why a timed-out run is deliberately not shrunk.
