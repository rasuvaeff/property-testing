# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## 1.1.0 — Unreleased

- Add `Gen::dictOf()` (`DictionaryArbitrary`): associative-array/map generator
  with keys and values from separate arbitraries; shrinks by size then values.
- Add `Gen::record()` (`RecordArbitrary`): fixed-shape map generator keyed by
  field name; shrinks each field through its arbitrary with the key set fixed.
- Add the `maxShrinks` parameter to `#[Property]`: cap the number of accepted
  shrink steps (`null` = no cap, `0` = disable shrinking).
- Document how to implement a custom `ArbitraryInterface`.

## 1.0.0 — 2026-06-29

- Initial release: property-based testing plugin for Testo.
