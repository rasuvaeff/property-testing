---
title: "Assume"
description: "Discard a property run when a precondition does not hold."
---

<!-- АВТОГЕНЕРАЦИЯ: docs/scripts/generate-api.mjs, проход рефлексии по src/ (docs/scripts/reflect-api.php) — не редактировать вручную. -->

# `Assume`

`Rasuvaeff\PropertyTesting\Assume`

**Класс** — [Исходник](https://github.com/rasuvaeff/property-testing/blob/master/src/Assume.php)

*Текст ниже — на английском, из PHPDoc в исходном коде.*

Discard a property run when a precondition does not hold.

An attempt discarded via Assume::that() is neither a failure nor a
successful check: the property runner retries with another random input. Use
it to skip combinations of generated values that are out of the
property's domain (e.g. "cap must be >= baseSeconds") instead of rejecting
them with a narrow Gen::filter(), which is slower.

Discards do not consume Property::$runs. The runner warns when more
than 90% of attempts are discarded and fails with GaveUpException when
Property::$maxDiscards is exceeded.

## Методы

### that()

```php
static that(bool $condition): void
```

Discard the current run unless the condition is true.

