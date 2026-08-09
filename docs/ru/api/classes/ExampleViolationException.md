---
title: "ExampleViolationException"
description: "Reported when an explicit example (a fixed input declared via the property's `Examples` method) fails."
---

<!-- АВТОГЕНЕРАЦИЯ: docs/scripts/generate-api.mjs, проход рефлексии по src/ (docs/scripts/reflect-api.php) — не редактировать вручную. -->

# `ExampleViolationException`

`Rasuvaeff\PropertyTesting\ExampleViolationException`

**Класс** — [Исходник](https://github.com/rasuvaeff/property-testing/blob/master/src/ExampleViolationException.php) — **Наследует:** `RuntimeException`

*Текст ниже — на английском, из PHPDoc в исходном коде.*

Reported when an explicit example (a fixed input declared via the property's
`Examples` method) fails. Examples run before the random inputs and are not
shrunk — they are already the minimal case the developer pinned — so this
carries the example's index and arguments verbatim.

## Методы

### getIndex()

```php
getIndex(): int
```

### getArguments()

```php
getArguments(): array
```

