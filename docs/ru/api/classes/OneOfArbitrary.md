---
title: "OneOfArbitrary"
description: "Picks a value uniformly at random from a fixed set."
---

<!-- АВТОГЕНЕРАЦИЯ: docs/scripts/generate-api.mjs, проход рефлексии по src/ (docs/scripts/reflect-api.php) — не редактировать вручную. -->

# `OneOfArbitrary`

`Rasuvaeff\PropertyTesting\Arbitrary\OneOfArbitrary`

**Класс** — [Исходник](https://github.com/rasuvaeff/property-testing/blob/master/src/Arbitrary/OneOfArbitrary.php)

*Текст ниже — на английском, из PHPDoc в исходном коде.*

Picks a value uniformly at random from a fixed set.

Values are used verbatim (they are not arbitraries). Earlier values are
considered "smaller": a failing value shrinks through the distinct values
listed before it, so put simpler values first. Because the index strictly
decreases on every step, shrinking terminates even when several values keep
failing. Use this for enumerations and small tagged unions.

## Методы

### generate()

```php
generate(Random $random): Shrinkable
```

