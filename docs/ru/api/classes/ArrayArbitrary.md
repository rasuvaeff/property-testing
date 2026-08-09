---
title: "ArrayArbitrary"
description: "Generates lists whose elements come from a delegate arbitrary and shrinks them by length toward the empty array, then element-by-element through each…"
---

<!-- АВТОГЕНЕРАЦИЯ: docs/scripts/generate-api.mjs, проход рефлексии по src/ (docs/scripts/reflect-api.php) — не редактировать вручную. -->

# `ArrayArbitrary`

`Rasuvaeff\PropertyTesting\Arbitrary\ArrayArbitrary`

**Класс** — [Исходник](https://github.com/rasuvaeff/property-testing/blob/master/src/Arbitrary/ArrayArbitrary.php)

*Текст ниже — на английском, из PHPDoc в исходном коде.*

Generates lists whose elements come from a delegate arbitrary and shrinks
them by length toward the empty array, then element-by-element through each
element's own shrink tree.

Element shrink trees are captured at generation time, so elements produced
by transformed arbitraries (\Rasuvaeff\PropertyTesting\Gen::map(),
\Rasuvaeff\PropertyTesting\Gen::flatMap()) shrink correctly.

## Методы

### generate()

```php
generate(Random $random): Shrinkable
```

