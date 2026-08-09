---
title: "CharsetStringArbitrary"
description: "Generates strings whose characters come from a fixed alphabet, and shrinks them by length toward the empty string, then character-by-character toward the…"
---

<!-- АВТОГЕНЕРАЦИЯ: docs/scripts/generate-api.mjs, проход рефлексии по src/ (docs/scripts/reflect-api.php) — не редактировать вручную. -->

# `CharsetStringArbitrary`

`Rasuvaeff\PropertyTesting\Arbitrary\CharsetStringArbitrary`

**Класс** — [Исходник](https://github.com/rasuvaeff/property-testing/blob/master/src/Arbitrary/CharsetStringArbitrary.php)

*Текст ниже — на английском, из PHPDoc в исходном коде.*

Generates strings whose characters come from a fixed alphabet, and shrinks
them by length toward the empty string, then character-by-character toward
the first alphabet character (list simpler characters first).

The alphabet is split per Unicode codepoint, so multibyte alphabets work;
duplicate characters are collapsed. Length is chosen uniformly within an
inclusive range.

## Методы

### generate()

```php
generate(Random $random): Shrinkable
```

