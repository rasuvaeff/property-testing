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

| Item | What | Effort | Notes |
|---|---|---|---|
| #3 | `Gen::dictOf($keyGen, $valGen)`, `Gen::record(['k' => $gen, ...])` | S | string-keyed arrays / DTO shapes; lists-only today |
| #6 | env override for `runs`/`seed` (`PROPERTY_RUNS`, `PROPERTY_SEED`) | S | dial up runs in CI / replay a seed suite-wide; defaults preserved |
| — | `maxShrinks` parameter on `#[Property]` | S | cap the shrink loop on expensive properties; default keeps current behaviour |
| — | combinators: `constant`, `elements(array)`, `char`, `uuid`, `datetime` | S–M | new `Gen` factories |
| #4 | README section "writing your own `ArbitraryInterface`" | S | docs only (could ship as `1.0.1` patch) |
| #7 | `classify`/`collect`/`sample` (distribution observability) | M | new public helper + stats collection in the interceptor; optional this wave |
| #5 | boundary-biased generation (0/±1/min/max, NaN/±INF for floats) | M | minor*; changes the seed sequence across versions. Prefer opt-in flag to stay fully safe |

\* #5 keeps the type contract intact (not a BC break), but seed reproducibility
*across versions* shifts. Sequence stability is not part of the public contract.
Document "seed reproducible within a version, not across versions", or gate
behind an opt-in generator flag.

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

## Suggested order

1. `1.1.0`: cheapest, safest first — #3 + #4 + `maxShrinks`.
2. Follow-up `1.x`: #6, combinators, then #7 / #5.
3. `2.0.0`: integrated shrinking + `flatMap`, planned as a separate effort.
