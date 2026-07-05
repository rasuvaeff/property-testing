# Roadmap — rasuvaeff/property-testing

Development plan derived from a consumer-perspective gap analysis. Current
version: `2.1.0`.

## Guiding split

The watershed is the shrinking model. Everything except integrated shrinking is
additive and ships in minor releases. Integrated shrinking changes the
`@api` `ArbitraryInterface::generate()` signature (value -> shrinkable tree),
which `roave/backward-compatibility-check` flags as a BC break, so it is a major.

`map`/`flatMap` shrinking cannot be fixed without it: `shrink(mixed $value)`
receives only the post-transform value, so the pre-image of `fn(x)` is
unrecoverable. `filter` is NOT affected — it delegates to the inner type.

## Wave 1 — `1.1.0` (minor, no BC break)

Additive only; no consumer migration required.

All of Wave 1 shipped together in `1.1.0` (status: **done**, on PR #4).

| Item | What | Status |
|---|---|---|
| #3 | `Gen::dictOf($keyGen, $valGen)`, `Gen::record(['k' => $gen, ...])` | done |
| #6 | env override for `runs`/`seed` (`PROPERTY_RUNS`, `PROPERTY_SEED`) | done |
| — | `maxShrinks` parameter on `#[Property]` | done |
| — | combinators: `constant`, `elements(array)`, `char`, `uuid`, `datetime` | done |
| #4 | README section "writing your own `ArbitraryInterface`" | done |
| #7 | `classify`/`sample` (distribution observability) | done |
| #5 | boundary-biased numeric generation (0/±1/min/max for ints, 0.0/min for floats) | done |

#5 ships **on by default** (no external consumers; the only consumers are this
author's own packages, whose property tests are under the same control). It keeps
the type contract intact (not a BC break) but shifts the generated sequence for a
given seed — seed reproducibility holds *within* a version, not across versions.
NaN/±INF are intentionally NOT part of the default bias (they break the in-range
contract of `floatBetween`/`float`); a `floatSpecial()` opt-in could add them
later. `classify`/`collect` introduce a process-local mutable static (`Classify`),
an accepted exception to the "only `Random` is mutable" invariant (see
`AGENTS.md`).

## Wave 2 — `2.0.0` (major, core rework)

Isolated single idea: shrink via a generation tree.

| Item | What | Effort | Notes |
|---|---|---|---|
| #1 | integrated shrinking — `generate()` returns a shrinkable tree | L | fixes `Gen::map` shrinking; rewrites every `*Arbitrary` + the interceptor shrink loop |
| #2 | `Gen::flatMap`/`bind` (dependent generators) | M | impossible before #1; removes the forced `Assume::that` workaround |

Ship with a migration guide. Keep this major free of unrelated additive work so
`release-check` / BC diagnostics / changelog stay focused on one contract change.

## Wave 3 — `2.1.0` (minor, consumer-driven additions)

Derived from a consumer-perspective review after 2.0.0. Additive only; the
`ArbitraryInterface` contract and existing seed sequences are untouched.

| Item | What | Status |
|---|---|---|
| coverage gate | `Classify::cover($cond, $label, $minPercent)` — under-covered label FAILS the property (`CoverageViolationException`) | done |
| charset strings | `Gen::stringFrom($alphabet, $min, $max)` — identifiers/hex/slugs without map-boilerplate | done |
| binary | `Gen::bytes($min, $max)` — raw bytes for parsers/codecs | done |
| enums | `Gen::enum(SomeEnum::class)` — shrinks toward earlier-declared cases | done |
| distinct lists | `Gen::uniqueArrayOf($el, $min, $max)` — shrinking preserves distinctness | done |
| intervals | `Gen::intRange($min, $max)` — ordered `[lo, hi]` on top of `flatMap` | done |
| float specials | `Gen::floatSpecial()` — opt-in NaN/±INF/-0.0/edges (default floats stay finite) | done |
| recursion | `Gen::recursive($leaf, $wrap, $maxDepth)` — bounded depth, 50/50 leaf-vs-branch per level | done |
| shrink debugging | `Gen::sampleShrinks()` — value + first shrink candidates for custom arbitraries | done |
| sized collections | optional `$min`/`$max` on `arrayOf`/`nonEmptyArrayOf`/`dictOf` | done |
| verbose runs | `PROPERTY_VERBOSE` env — log every run's generated arguments | done |

## Consumer gap analysis vs. analogs (2026-07-05)

After `2.1.0`, a consumer-perspective review compared the surface against the
major property-testing libraries. `property-testing` is already state of the
art on **integrated shrinking** (Hedgehog model) and carries a **coverage gate**
(`Classify::cover`) that most libraries lack. The remaining gaps, and who has
each feature:

| Feature | Hypothesis | fast-check | jqwik | Hedgehog | Eris (PHP) | here |
|---|:--:|:--:|:--:|:--:|:--:|:--:|
| Integrated shrinking | ✓ | ✓ | ✓ | ✓ | ✗ | ✓ |
| Coverage gate (`cover`) | ~ | ✗ | ✓ | ✗ | ✗ | ✓ |
| Stateful / model-based | ✓ | ✓ | ✓ | ✓ | ✗ | ✗ |
| Explicit examples (`@example`) | ✓ | ✓ | ✓ | ✗ | ✗ | ✗ |
| Persist last failure + auto-replay | ✓ | ~ | ✓ | ✗ | ✗ | ✗ |
| Domain arbitraries + regex/`stringMatching` | ✓ | ✓✓ | ✓ | ~ | ~ | ✗ |
| In-body draw (`data.draw()`/`gen()`) | ✓ | ✓ | ✗ | ✗ | ✗ | ✗ |
| Targeted PBT (`target()`) | ✓ | ✗ | ✓ | ✗ | ✗ | ✗ |
| Function generation (CoArbitrary) | ✓ | ✓ | ~ | ✗ | ✗ | ✗ |
| Per-example deadline/timeout | ✓ | ~ | ✓ | ✗ | ✗ | ✗ |

All gap items below are **additive** — none touch the `ArbitraryInterface`
contract or the existing seed sequences, so all ship as minors (no major).

## Wave 4 — `2.2.0` (stateful / model-based testing)

The single biggest gap: no PHP peer has it, every non-PHP analog does. Tests
*sequences* of operations against a simplified model, then shrinks the failing
sequence. Highest-value target in this monorepo — covers invariants unreachable
by single-shot properties: `bulkhead` (concurrency counters), `yii3-feature-flags`
/`yii3-settings` (set→get), `yii3-outbox` (enqueue/dequeue/markProcessed),
`yii3-idempotency`, cache backends.

| Item | What | Status |
|---|---|---|
| T1.1 | command/model-based API: a `Command` interface (precondition, apply-to-model, run-against-SUT, postcondition) + a generator producing valid command sequences that shrink by dropping/simplifying steps | planned |

This **reverses** the former "no stateful testing" non-goal below — the gap
analysis judged it worth the API surface.

## Wave 5 — `2.3.0` (regression ergonomics)

| Item | What | Status |
|---|---|---|
| T1.2 | explicit examples — fixed inputs that always run alongside random ones (`@Example`-style, or an `examples` arg on `#[Property]`); pins a found bug as a permanent case | planned |
| T1.3 | persist the minimal counterexample of a failing property and auto-replay it first on the next run (analog of Hypothesis's example DB / jqwik samples); opt-in storage path, gitignore-friendly | planned |

## Wave 6 — `2.4.0` (generator catalog + in-body draw)

| Item | What | Status |
|---|---|---|
| T2.4 | domain arbitraries: `Gen::regex($pattern)` / `stringMatching`, `email`, `url`, `ipv4`, `json` — removes the documented `Gen::map` workaround (root `AGENTS.md` notes `Gen::stringMatching` does not exist) | planned |
| T2.5 | in-body dependent draw (`Gen::draw($arb)` inside the property body) for when >1 dependent value makes nested `flatMap` awkward | planned |

## Backlog — Tier 3 (niche, unscheduled)

| Item | What |
|---|---|
| T3.6 | targeted PBT — `target($metric)` guiding the search toward maximizing a value (e.g. drive backoff delay to its cap) |
| T3.7 | function generation (CoArbitrary) — random pure functions for higher-order code (comparators, mappers) |
| T3.8 | per-example deadline/timeout — catch pathological slow inputs (relevant to `retry`/`bulkhead` timing) |

## Non-goals (by design, not gaps)

- Testo-only (no PHPUnit/Pest adapter).
- Attribute-only API for the property declaration (no functional `forAll(...)`;
  an in-body `Gen::draw()` helper in Wave 6 is not the same thing).

(Former non-goal "no stateful / model-based testing" promoted to Wave 4 by the
2026-07-05 gap analysis.)

## Status

- `1.1.0` — entire Wave 1 (done, PR #4).
- `2.0.0` — integrated shrinking + `flatMap` (done, PR #5, released 2026-07-02).
- `2.1.0` — entire Wave 3 (done, PR #6, released 2026-07-05).
- `2.2.0` — Wave 4, stateful / model-based testing (planned).
- `2.3.0` — Wave 5, regression ergonomics (planned).
- `2.4.0` — Wave 6, generator catalog + in-body draw (planned).
- Backlog — Tier 3 items, unscheduled.
