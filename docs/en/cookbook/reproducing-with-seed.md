---
title: "Cookbook: reproducing with a seed"
---

# Cookbook: reproducing with a seed

A falsified property reports a `seed`. This page walks through the three
ways to use it, and how `PROPERTY_VERBOSE` fills the gap between "which seed"
and "what actually happened during that run."

## Why seeds reproduce exactly

`Random` (the type every generator draws from) wraps an object-scoped
`\Random\Randomizer` (MT19937), independent of PHP's global `mt_rand` state.
Two `Random` instances constructed with the same seed produce the identical
draw sequence regardless of what else in the process called random functions
in between — that's what makes a reported seed a reliable replay key rather
than a best-effort hint. See [`Random`](/en/api/classes/Random).

## 1. Pin the seed on the attribute

The most direct route once you've found a failure locally:

```php
#[Property(runs: 200, seed: 918273645)]
public function everyIntIsEven(int $n): void
{
    Assert::true($n % 2 === 0);
}
```

With `seed` pinned, this property always generates the same sequence — useful
while you're actively debugging a specific failure, but remember to remove it
before committing unless you're intentionally locking a regression case (for
that, prefer [explicit examples](/en/explicit-examples) or the
[regression corpus](/en/regression-corpus) — both are designed to persist,
where an attribute-pinned seed is easy to forget and leave in place, quietly
narrowing the property to one input forever).

## 2. Override via environment, no code change

`PROPERTY_SEED` sets the seed for every property whose attribute *doesn't*
pin one — a pinned `seed:` still wins over the env var:

```bash
docker run --rm -v "$PWD":/app -w /app -e PROPERTY_SEED=918273645 \
  composer:2 vendor/bin/testo
```

Handy in CI when a nightly run reports a seed and you want to replay it
without editing the test file, or when reproducing locally without leaving a
diff behind.

## 3. Watch it happen with `PROPERTY_VERBOSE`

A seed replays the *inputs*; `PROPERTY_VERBOSE` shows what the property did
with them — every run's generated arguments, plus one line per **accepted**
shrink step on failure:

```bash
docker run --rm -v "$PWD":/app -w /app -e PROPERTY_SEED=918273645 \
  -e PROPERTY_VERBOSE=1 composer:2 vendor/bin/testo
```

```
run 1: n=412
run 2: n=847
shrink step 1: n=847 -> 424
shrink step 2: n=424 -> 213
shrink step 3: n=213 -> 107
shrink step 4: n=107 -> 1
```

This is the tool for "the shrunk value looks strange — how did the shrinker
get there," or "the property discards a lot — which inputs are being
rejected before I even see a failure." It's noisy by design (every run, not
just the failing one), so pair it with a pinned/env seed rather than running
it over an unconstrained full suite.

## Seeds have a shelf life within a major version only

Two `Random(seed: 42)` instances agree today, but reproducibility is
guaranteed *within* one major version, not across a release that shifts the
generation sequence (a new boundary-bias rule, a changed draw order). Don't
bake a bare `#[Property(seed: ...)]` into a long-lived regression suite as
your only defense — see [Regression corpus](/en/regression-corpus) for the
mechanism (`PROPERTY_DB`) that already accounts for this via
`SEQUENCE_EPOCH`, and [explicit examples](/en/explicit-examples) for pinning
a fixed input independent of any generator or seed at all.
