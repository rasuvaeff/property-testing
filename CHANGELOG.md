# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## 2.2.0 — Unreleased

Stateful / model-based testing (Wave 4); additive, no BC breaks, seed sequences
of existing generators are unchanged.

- Add stateful / model-based testing in the new `Rasuvaeff\PropertyTesting\StateMachine`
  namespace: the `Command` interface (`preCondition`/`nextState`/`run`/`postCondition`,
  `\Stringable` label), `Gen::commands($initialModel, $commandGenerators, $minLength, $maxLength)`
  producing a `CommandSequence` arbitrary, and `StateMachine::check($sequence, $systemFactory)`
  to drive the sequence against a fresh system under test. Generated sequences are
  valid by construction; shrinking drops command blocks (down to a single command,
  isolating a failing middle step) and simplifies each command's parameters. A failed
  postcondition throws `PostconditionViolation` naming the failing step and trace.
- Render `\Stringable` arguments via `__toString()` in counterexamples and verbose
  logs (previously objects showed only their class name).
- Report the failure of the *shrunk* run in the counterexample, not the original
  draw — the `Failure:` line now matches the minimised `Shrunk:` arguments.

## 2.1.0 — 2026-07-05

Consumer-driven additions (Wave 3); no BC breaks, seed sequences of existing
generators are unchanged.

- Add `Classify::cover($condition, $label, $minPercent)`: a coverage
  requirement — the property fails with the new `CoverageViolationException`
  when the label occurs in fewer than `$minPercent` of passing runs, making
  vacuous passes a CI failure instead of a printed hint.
- Add `Gen::stringFrom($alphabet, $min, $max)` (`CharsetStringArbitrary`):
  strings over a fixed (possibly multibyte) alphabet; shrinks by length, then
  each character toward the first alphabet character.
- Add `Gen::bytes($min, $max)` (`BytesArbitrary`): raw byte strings for
  parsers/codecs; shrinks by length, then each byte toward `"\x00"`.
- Add `Gen::enum(SomeEnum::class)`: one enum case, shrinking toward
  earlier-declared cases.
- Add `Gen::uniqueArrayOf($element, $min, $max)` (`UniqueArrayArbitrary`):
  lists of pairwise-distinct elements; shrinking preserves distinctness.
- Add `Gen::intRange($min, $max)`: ordered pairs `[lo, hi]` with `lo <= hi`
  built on `flatMap`, replacing `Assume::that()` discards for intervals.
- Add `Gen::floatSpecial()`: opt-in `NAN`/`±INF`/`-0.0`/representation-edge
  floats (the default `float()`/`floatBetween()` stay finite and in range).
- Add `Gen::recursive($leaf, $wrap, $maxDepth)`: bounded recursive structures
  built by lifting the previous level's arbitrary.
- Add `Gen::sampleShrinks()`: generate one value and list its first shrink
  candidates — a debugging aid for custom arbitraries.
- Add optional size bounds to `Gen::arrayOf()`, `Gen::nonEmptyArrayOf()` and
  `Gen::dictOf()`.
- Honour `PROPERTY_VERBOSE` to log every run's generated arguments.

## 2.0.0 — 2026-07-02

Integrated shrinking: shrink candidates now come from the generation tree, so
transformed generators shrink correctly. See [UPGRADE.md](UPGRADE.md).

- **BC break**: `ArbitraryInterface::generate()` returns a `Shrinkable` (the
  value plus a lazy tree of smaller candidates) and
  `ArbitraryInterface::shrink(mixed)` is removed. `#[Property]` test code using
  `Gen` factories is unaffected; custom arbitraries must be rewritten.
- Add `Shrinkable`: an immutable value/lazy-shrink-tree node with `leaf()`,
  `of()`, `shrinks()` and `map()`.
- Add `Gen::flatMap()` (`FlatMappedArbitrary`): dependent generators (monadic
  bind). The source value shrinks with the dependent value regenerated from the
  run's seed, then the dependent value shrinks with the source held fixed.
- `Gen::map()` now shrinks: candidates come from the inner tree with the
  mapping re-applied (previously mapped values did not shrink at all).
- `Gen::frequency()` shrinks within the branch that generated the value
  (previously it delegated to every branch by type discrimination).
- `Gen::oneOf()`/`Gen::elements()` shrink toward earlier-listed values only,
  which guarantees termination; list simpler values first.
- Integers shrink by halving the distance to the in-range target — a binary
  search that finds the exact failing boundary of monotone predicates.
- Shrunk counterexamples for a given 1.x seed may differ (typically smaller);
  seed reproducibility holds within a major version, not across.

## 1.1.0 — 2026-07-01

- Add `Gen::dictOf()` (`DictionaryArbitrary`): associative-array/map generator
  with keys and values from separate arbitraries; shrinks by size then values.
- Add `Gen::record()` (`RecordArbitrary`): fixed-shape map generator keyed by
  field name; shrinks each field through its arbitrary with the key set fixed.
- Add generators `Gen::constant()` (`ConstantArbitrary`), `Gen::elements()`,
  `Gen::char()`, `Gen::uuid()` (`UuidArbitrary`) and `Gen::datetime()`
  (`DateTimeArbitrary`).
- Add the `maxShrinks` parameter to `#[Property]`: cap the number of accepted
  shrink steps (`null` = no cap, `0` = disable shrinking).
- Bias numeric generators toward in-range boundary values (`0`, `±1`, `min`,
  `max` for ints; `0.0`, `min` for floats), where bugs cluster. Shrinking is
  unchanged. This shifts the generated sequence for a given seed.
- Add `Classify` (`label`/`when`): record per-run distribution labels; the runner
  reports the share of passing runs that hit each label.
- Add `Gen::sample()`: eagerly generate values from an arbitrary for inspection.
- Honour `PROPERTY_RUNS` and `PROPERTY_SEED` environment variables to override
  the run count and the seed of unseeded properties.
- Document how to implement a custom `ArbitraryInterface`.

## 1.0.0 — 2026-06-29

- Initial release: property-based testing plugin for Testo.
