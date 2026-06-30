# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## 1.1.0 — Unreleased

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
