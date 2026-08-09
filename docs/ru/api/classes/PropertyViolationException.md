---
title: "PropertyViolationException"
description: "Reported when a property is falsified."
---

<!-- АВТОГЕНЕРАЦИЯ: docs/scripts/generate-api.mjs, проход рефлексии по src/ (docs/scripts/reflect-api.php) — не редактировать вручную. -->

# `PropertyViolationException`

`Rasuvaeff\PropertyTesting\PropertyViolationException`

**Класс** — [Исходник](https://github.com/rasuvaeff/property-testing/blob/master/src/PropertyViolationException.php) — **Наследует:** `RuntimeException`

*Текст ниже — на английском, из PHPDoc в исходном коде.*

Reported when a property is falsified.

Carries the CounterExample so reporters (and tests of this package)
can inspect the seed and the shrunk arguments. Its message renders a
human-readable summary.

## Методы

### getCounterExample()

```php
getCounterExample(): CounterExample
```

