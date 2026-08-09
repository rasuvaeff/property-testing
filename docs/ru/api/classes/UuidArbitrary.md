---
title: "UuidArbitrary"
description: "Generates RFC 4122 version 4 (random) UUID strings in the canonical `xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx` form, where the version nibble is `4` and the…"
---

<!-- АВТОГЕНЕРАЦИЯ: docs/scripts/generate-api.mjs, проход рефлексии по src/ (docs/scripts/reflect-api.php) — не редактировать вручную. -->

# `UuidArbitrary`

`Rasuvaeff\PropertyTesting\Arbitrary\UuidArbitrary`

**Класс** — [Исходник](https://github.com/rasuvaeff/property-testing/blob/master/src/Arbitrary/UuidArbitrary.php)

*Текст ниже — на английском, из PHPDoc в исходном коде.*

Generates RFC 4122 version 4 (random) UUID strings in the canonical
`xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx` form, where the version nibble is `4`
and the variant nibble is one of `8`, `9`, `a`, `b`.

A UUID is an opaque identifier with no meaningful ordering, so it does not
shrink. Randomness comes from the seeded Random, so generated UUIDs are
reproducible but NOT suitable for security purposes.

## Методы

### generate()

```php
generate(Random $random): Shrinkable
```

