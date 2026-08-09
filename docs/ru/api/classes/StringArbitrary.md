---
title: "StringArbitrary"
description: "Generates random strings and shrinks them by length toward the empty string, then character-by-character toward 'a'."
---

<!-- АВТОГЕНЕРАЦИЯ: docs/scripts/generate-api.mjs, проход рефлексии по src/ (docs/scripts/reflect-api.php) — не редактировать вручную. -->

# `StringArbitrary`

`Rasuvaeff\PropertyTesting\Arbitrary\StringArbitrary`

**Класс** — [Исходник](https://github.com/rasuvaeff/property-testing/blob/master/src/Arbitrary/StringArbitrary.php)

*Текст ниже — на английском, из PHPDoc в исходном коде.*

Generates random strings and shrinks them by length toward the empty string,
then character-by-character toward 'a'.

Two alphabets are available: an ASCII printable subset (32..126) and the
full Unicode space via mb_chr(). Length is chosen uniformly within an
inclusive range.

## Методы

### generate()

```php
generate(Random $random): Shrinkable
```

