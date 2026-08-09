---
title: "ConstantArbitrary"
description: "Always produces the same fixed value."
---

<!-- АВТОГЕНЕРАЦИЯ: docs/scripts/generate-api.mjs, проход рефлексии по src/ (docs/scripts/reflect-api.php) — не редактировать вручную. -->

# `ConstantArbitrary`

`Rasuvaeff\PropertyTesting\Arbitrary\ConstantArbitrary`

**Класс** — [Исходник](https://github.com/rasuvaeff/property-testing/blob/master/src/Arbitrary/ConstantArbitrary.php)

*Текст ниже — на английском, из PHPDoc в исходном коде.*

Always produces the same fixed value. Useful as a building block for composite
generators (e.g. a `record` field that is held constant) and for pinning one
parameter while others vary.

There is nothing smaller than a constant, so it does not shrink.

## Методы

### generate()

```php
generate(Random $random): Shrinkable
```

