---
title: "NullableArbitrary"
description: "Wraps another arbitrary and additionally yields `null` with roughly even odds."
---

<!-- АВТОГЕНЕРАЦИЯ: docs/scripts/generate-api.mjs, проход рефлексии по src/ (docs/scripts/reflect-api.php) — не редактировать вручную. -->

# `NullableArbitrary`

`Rasuvaeff\PropertyTesting\Arbitrary\NullableArbitrary`

**Класс** — [Исходник](https://github.com/rasuvaeff/property-testing/blob/master/src/Arbitrary/NullableArbitrary.php)

*Текст ниже — на английском, из PHPDoc в исходном коде.*

Wraps another arbitrary and additionally yields `null` with roughly even odds.

Shrinking prefers `null` over descending into the inner value's tree.

## Методы

### generate()

```php
generate(Random $random): Shrinkable
```

