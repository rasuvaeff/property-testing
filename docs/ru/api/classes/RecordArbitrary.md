---
title: "RecordArbitrary"
description: "Fixed-shape associative array: produces a map with one value per named field, each drawn from that field's arbitrary."
---

<!-- АВТОГЕНЕРАЦИЯ: docs/scripts/generate-api.mjs, проход рефлексии по src/ (docs/scripts/reflect-api.php) — не редактировать вручную. -->

# `RecordArbitrary`

`Rasuvaeff\PropertyTesting\Arbitrary\RecordArbitrary`

**Класс** — [Исходник](https://github.com/rasuvaeff/property-testing/blob/master/src/Arbitrary/RecordArbitrary.php)

*Текст ниже — на английском, из PHPDoc в исходном коде.*

Fixed-shape associative array: produces a map with one value per named field,
each drawn from that field's arbitrary. Useful for generating DTO-shaped
payloads where every key is known up front (the property receives the record
as a single string-keyed array argument).

Shrinking keeps the key set fixed and shrinks one field at a time through
that field's own shrink tree, so each value shrinks within its domain.

## Методы

### generate()

```php
generate(Random $random): Shrinkable
```

