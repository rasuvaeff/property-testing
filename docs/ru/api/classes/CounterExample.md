---
title: "CounterExample"
description: "CounterExample — class в справочнике API property-testing."
---

<!-- АВТОГЕНЕРАЦИЯ: docs/scripts/generate-api.mjs, проход рефлексии по src/ (docs/scripts/reflect-api.php) — не редактировать вручную. -->

# `CounterExample`

`Rasuvaeff\PropertyTesting\CounterExample`

**Класс** — [Исходник](https://github.com/rasuvaeff/property-testing/blob/master/src/CounterExample.php)

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

### toJson()

```php
toJson(bool $pretty): string
```

- `$pretty` — undefined

### toExamplesCode()

```php
toExamplesCode(string $methodName): string
```

- `$methodName` — undefined

