---
title: "BytesArbitrary"
description: "Generates raw byte strings (every byte 0..255, not printable text) and shrinks them by length toward the empty string, then byte-by-byte toward NUL…"
---

<!-- АВТОГЕНЕРАЦИЯ: docs/scripts/generate-api.mjs, проход рефлексии по src/ (docs/scripts/reflect-api.php) — не редактировать вручную. -->

# `BytesArbitrary`

`Rasuvaeff\PropertyTesting\Arbitrary\BytesArbitrary`

**Класс** — [Исходник](https://github.com/rasuvaeff/property-testing/blob/master/src/Arbitrary/BytesArbitrary.php)

*Текст ниже — на английском, из PHPDoc в исходном коде.*

Generates raw byte strings (every byte 0..255, not printable text) and
shrinks them by length toward the empty string, then byte-by-byte toward
NUL ("\x00"). Useful for parsers, codecs and binary protocols.

## Методы

### generate()

```php
generate(Random $random): Shrinkable
```

