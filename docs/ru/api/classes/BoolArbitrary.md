---
title: "BoolArbitrary"
description: "Generates booleans."
---

<!-- АВТОГЕНЕРАЦИЯ: docs/scripts/generate-api.mjs, проход рефлексии по src/ (docs/scripts/reflect-api.php) — не редактировать вручную. -->

# `BoolArbitrary`

`Rasuvaeff\PropertyTesting\Arbitrary\BoolArbitrary`

**Класс** — [Исходник](https://github.com/rasuvaeff/property-testing/blob/master/src/Arbitrary/BoolArbitrary.php)

*Текст ниже — на английском, из PHPDoc в исходном коде.*

Generates booleans. false is the "smaller" boolean: true shrinks to false,
false is terminal.

## Методы

### generate()

```php
generate(Random $random): Shrinkable
```

