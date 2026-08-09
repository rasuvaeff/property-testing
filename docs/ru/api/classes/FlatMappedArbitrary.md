---
title: "FlatMappedArbitrary"
description: "Dependent generators (monadic bind): each value produced by the source arbitrary is fed into a closure that returns the arbitrary generating the final…"
---

<!-- АВТОГЕНЕРАЦИЯ: docs/scripts/generate-api.mjs, проход рефлексии по src/ (docs/scripts/reflect-api.php) — не редактировать вручную. -->

# `FlatMappedArbitrary`

`Rasuvaeff\PropertyTesting\Arbitrary\FlatMappedArbitrary`

**Класс** — [Исходник](https://github.com/rasuvaeff/property-testing/blob/master/src/Arbitrary/FlatMappedArbitrary.php)

*Текст ниже — на английском, из PHPDoc в исходном коде.*

Dependent generators (monadic bind): each value produced by the source
arbitrary is fed into a closure that returns the arbitrary generating the
final value. Use it when one input's domain depends on another, e.g. a list
plus a valid index into that list — instead of discarding invalid pairs via
\Rasuvaeff\PropertyTesting\Assume::that().

Shrinking works on both levels: first the source value shrinks (the closure
is re-applied and the dependent arbitrary regenerates with the same captured
seed, so runs stay reproducible), then the dependent value shrinks through
its own tree with the source value held fixed. The closure must be pure — it
runs once per generated value and once per visited source candidate.

## Методы

### generate()

```php
generate(Random $random): Shrinkable
```

