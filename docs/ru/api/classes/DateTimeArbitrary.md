---
title: "DateTimeArbitrary"
description: "Generates UTC DateTimeImmutable values with a Unix timestamp drawn uniformly from an inclusive range, and shrinks toward the Unix epoch…"
---

<!-- АВТОГЕНЕРАЦИЯ: docs/scripts/generate-api.mjs, проход рефлексии по src/ (docs/scripts/reflect-api.php) — не редактировать вручную. -->

# `DateTimeArbitrary`

`Rasuvaeff\PropertyTesting\Arbitrary\DateTimeArbitrary`

**Класс** — [Исходник](https://github.com/rasuvaeff/property-testing/blob/master/src/Arbitrary/DateTimeArbitrary.php)

*Текст ниже — на английском, из PHPDoc в исходном коде.*

Generates UTC DateTimeImmutable values with a Unix timestamp drawn
uniformly from an inclusive range, and shrinks toward the Unix epoch
(1970-01-01T00:00:00Z), clamped to the configured range.

## Методы

### generate()

```php
generate(Random $random): Shrinkable
```

