---
title: "UniqueArrayArbitrary"
description: "Generates lists of pairwise-distinct elements (strict comparison) drawn from a delegate arbitrary, and shrinks them by length toward the empty array,…"
---

<!-- АВТОГЕНЕРАЦИЯ: docs/scripts/generate-api.mjs, проход рефлексии по src/ (docs/scripts/reflect-api.php) — не редактировать вручную. -->

# `UniqueArrayArbitrary`

`Rasuvaeff\PropertyTesting\Arbitrary\UniqueArrayArbitrary`

**Класс** — [Исходник](https://github.com/rasuvaeff/property-testing/blob/master/src/Arbitrary/UniqueArrayArbitrary.php)

*Текст ниже — на английском, из PHPDoc в исходном коде.*

Generates lists of pairwise-distinct elements (strict comparison) drawn from
a delegate arbitrary, and shrinks them by length toward the empty array, then
element-by-element through each element's own tree — accepting only
candidates that keep the list distinct.

Generation draws a size, then draws elements, skipping duplicates. Drawing is
bounded: after self::MAX_ATTEMPTS_PER_ELEMENT attempts per requested
element the generator settles for the distinct elements found so far — the
result may be smaller than the drawn size, mirroring dictOf's key-collision
behaviour. An element space too small to reach the minimum size throws
GenerationExhausted rather than hand the property a too-small list.

## Методы

### generate()

```php
generate(Random $random): Shrinkable
```

