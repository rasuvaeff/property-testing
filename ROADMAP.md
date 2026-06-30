# Roadmap — rasuvaeff/property-testing

Development plan derived from a consumer-perspective gap analysis. Current
version: `1.0.0`.

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

## Non-goals (by design, not gaps)

- Testo-only (no PHPUnit/Pest adapter).
- Attribute-only API (no functional `forAll(...)`).
- No stateful / model-based (command-sequence) testing.

## Status

- `1.1.0` — entire Wave 1 (done, PR #4).
- `2.0.0` — integrated shrinking + `flatMap`, planned as a separate effort.
