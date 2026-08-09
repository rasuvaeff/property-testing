---
title: "IntArbitrary"
description: "Generates integers within an inclusive range and shrinks them toward zero (clamped into the range, so the target of a zero-free range is its nearest bound)."
---

<!-- АВТОГЕНЕРАЦИЯ: docs/scripts/generate-api.mjs, проход рефлексии по src/ (docs/scripts/reflect-api.php) — не редактировать вручную. -->

# `IntArbitrary`

`Rasuvaeff\PropertyTesting\Arbitrary\IntArbitrary`

**Класс** — [Исходник](https://github.com/rasuvaeff/property-testing/blob/master/src/Arbitrary/IntArbitrary.php)

*Текст ниже — на английском, из PHPDoc в исходном коде.*

Generates integers within an inclusive range and shrinks them toward zero
(clamped into the range, so the target of a zero-free range is its nearest
bound).

Generation is biased: roughly one draw in BIAS_DENOMINATOR returns an
in-range boundary value (0, ±1, min, max) instead of a uniform one, because
bugs cluster at edges.

The shrink tree halves the distance to the target: the target itself first,
then candidates progressively closer to the failing value, each with its own
subtree toward the same target — a binary search for the minimal failing
integer.

## Методы

### generate()

```php
generate(Random $random): Shrinkable
```

