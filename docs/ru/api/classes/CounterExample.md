---
title: "CounterExample"
description: "Minimal failing input for a property, captured at falsification time."
---

<!-- АВТОГЕНЕРАЦИЯ: docs/scripts/generate-api.mjs, проход рефлексии по src/ (docs/scripts/reflect-api.php) — не редактировать вручную. -->

# `CounterExample`

`Rasuvaeff\PropertyTesting\CounterExample`

**Класс** — [Исходник](https://github.com/rasuvaeff/property-testing/blob/master/src/CounterExample.php)

*Текст ниже — на английском, из PHPDoc в исходном коде.*

Minimal failing input for a property, captured at falsification time.

Carries both the original (randomly generated) counterexample and the
shrunk (minimised) one, plus the seed needed to reproduce the run.

## Свойства

| Свойство | Тип | Readonly |
|---|---|---|
| `seed` | `int` | yes |
| `runsBeforeFailure` | `int` | yes |
| `originalArguments` | `array` | yes |
| `shrunkArguments` | `array` | yes |
| `shrinkSteps` | `int` | yes |
| `failure` | `?Throwable` | yes |
| `skips` | `int` | yes |
| `shrinkTrials` | `int` | yes |

## Методы

### toArray()

```php
toArray(): array
```

Machine-readable representation suitable for reporters and serialization.

### toJson()

```php
toJson(bool $pretty): string
```

### toExamplesCode()

```php
toExamplesCode(string $methodName): string
```

