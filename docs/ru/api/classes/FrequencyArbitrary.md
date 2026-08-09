---
title: "FrequencyArbitrary"
description: "Weighted choice among several arbitraries: each `[weight, arbitrary]` pair is picked with probability proportional to its (positive integer) weight, then…"
---

<!-- АВТОГЕНЕРАЦИЯ: docs/scripts/generate-api.mjs, проход рефлексии по src/ (docs/scripts/reflect-api.php) — не редактировать вручную. -->

# `FrequencyArbitrary`

`Rasuvaeff\PropertyTesting\Arbitrary\FrequencyArbitrary`

**Класс** — [Исходник](https://github.com/rasuvaeff/property-testing/blob/master/src/Arbitrary/FrequencyArbitrary.php)

*Текст ниже — на английском, из PHPDoc в исходном коде.*

Weighted choice among several arbitraries: each `[weight, arbitrary]` pair is
picked with probability proportional to its (positive integer) weight, then
the chosen arbitrary produces the value.

The chosen branch's shrink tree is returned as-is, so shrinking stays within
the branch that actually generated the value.

## Методы

### generate()

```php
generate(Random $random): Shrinkable
```

