---
title: "MappedArbitrary"
description: "Transforms each value produced by a delegate arbitrary through a pure function."
---

<!-- АВТОГЕНЕРАЦИЯ: docs/scripts/generate-api.mjs, проход рефлексии по src/ (docs/scripts/reflect-api.php) — не редактировать вручную. -->

# `MappedArbitrary`

`Rasuvaeff\PropertyTesting\Arbitrary\MappedArbitrary`

**Класс** — [Исходник](https://github.com/rasuvaeff/property-testing/blob/master/src/Arbitrary/MappedArbitrary.php)

*Текст ниже — на английском, из PHPDoc в исходном коде.*

Transforms each value produced by a delegate arbitrary through a pure function.

The whole shrink tree is mapped: shrinking happens in the inner (source)
domain and the function is re-applied to every candidate, so the shrunk
counterexample is reported in the transformed domain. The function must be
pure — it runs once per generated value and once per visited candidate.

## Методы

### generate()

```php
generate(Random $random): Shrinkable
```

