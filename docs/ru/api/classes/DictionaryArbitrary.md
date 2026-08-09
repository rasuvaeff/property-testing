---
title: "DictionaryArbitrary"
description: "Generates associative arrays (maps) whose keys come from a key arbitrary and whose values come from a value arbitrary, then shrinks them by size toward…"
---

<!-- АВТОГЕНЕРАЦИЯ: docs/scripts/generate-api.mjs, проход рефлексии по src/ (docs/scripts/reflect-api.php) — не редактировать вручную. -->

# `DictionaryArbitrary`

`Rasuvaeff\PropertyTesting\Arbitrary\DictionaryArbitrary`

**Класс** — [Исходник](https://github.com/rasuvaeff/property-testing/blob/master/src/Arbitrary/DictionaryArbitrary.php)

*Текст ниже — на английском, из PHPDoc в исходном коде.*

Generates associative arrays (maps) whose keys come from a key arbitrary and
whose values come from a value arbitrary, then shrinks them by size toward the
empty map and value-by-value through each value's own shrink tree (keys are
never shrunk).

Keys must be PHP array keys (int or string); a key arbitrary that produces
anything else is a configuration error and throws. Generation draws a size,
then draws distinct keys (each paired with a value) up to an attempt budget,
so seeded runs are reproducible. When the key space runs out of fresh keys the
map may be smaller than the drawn size, but it is NEVER smaller than
$minSize: an unreachable minimum throws GenerationExhausted
rather than hand the property a too-small map.

## Методы

### generate()

```php
generate(Random $random): Shrinkable
```

