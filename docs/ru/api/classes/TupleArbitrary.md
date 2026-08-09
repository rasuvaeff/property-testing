---
title: "TupleArbitrary"
description: "Fixed-arity tuple: produces a list with one value per element arbitrary, in order."
---

<!-- АВТОГЕНЕРАЦИЯ: docs/scripts/generate-api.mjs, проход рефлексии по src/ (docs/scripts/reflect-api.php) — не редактировать вручную. -->

# `TupleArbitrary`

`Rasuvaeff\PropertyTesting\Arbitrary\TupleArbitrary`

**Класс** — [Исходник](https://github.com/rasuvaeff/property-testing/blob/master/src/Arbitrary/TupleArbitrary.php)

*Текст ниже — на английском, из PHPDoc в исходном коде.*

Fixed-arity tuple: produces a list with one value per element arbitrary, in
order. Useful for generating several correlated parameters as a single value
(the property receives the tuple as one array argument and destructures it).

Shrinking keeps the arity fixed and shrinks one position at a time through
that position's own shrink tree, so each component shrinks within its domain.

## Методы

### generate()

```php
generate(Random $random): Shrinkable
```

