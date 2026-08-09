---
title: "FloatArbitrary"
description: "Generates floats in the half-open range [min, max)."
---

<!-- АВТОГЕНЕРАЦИЯ: docs/scripts/generate-api.mjs, проход рефлексии по src/ (docs/scripts/reflect-api.php) — не редактировать вручную. -->

# `FloatArbitrary`

`Rasuvaeff\PropertyTesting\Arbitrary\FloatArbitrary`

**Класс** — [Исходник](https://github.com/rasuvaeff/property-testing/blob/master/src/Arbitrary/FloatArbitrary.php)

*Текст ниже — на английском, из PHPDoc в исходном коде.*

Generates floats in the half-open range [min, max).

Generation is biased: roughly one draw in BIAS_DENOMINATOR returns an
in-range boundary value (0.0 or min) instead of a uniform one, because bugs
cluster at edges. The exclusive upper bound is never emitted.

Shrinking floats reliably is hard (no natural "smallest" value), so the
shrink tree has a single candidate: zero, clamped into the configured range.
For fine-grained shrinking on a numeric input, generate an integer and
\Rasuvaeff\PropertyTesting\Gen::map() it to a float — with integrated
shrinking the mapped value shrinks through the integer's tree.

## Методы

### generate()

```php
generate(Random $random): Shrinkable
```

