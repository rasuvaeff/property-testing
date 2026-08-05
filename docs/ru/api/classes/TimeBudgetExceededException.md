---
title: "TimeBudgetExceededException"
description: "TimeBudgetExceededException — class в справочнике API property-testing."
---

<!-- АВТОГЕНЕРАЦИЯ: docs/scripts/generate-api.mjs, проход рефлексии по src/ (docs/scripts/reflect-api.php) — не редактировать вручную. -->

# `TimeBudgetExceededException`

`Rasuvaeff\PropertyTesting\TimeBudgetExceededException`

**Класс** — [Исходник](https://github.com/rasuvaeff/property-testing/blob/master/src/TimeBudgetExceededException.php) — **Наследует:** `RuntimeException`

## Свойства

| Свойство | Тип | Readonly |
|---|---|---|
| `propertyName` | `string` | yes |
| `budgetMs` | `int` | yes |
| `elapsedMs` | `float` | yes |
| `successfulRuns` | `int` | yes |
| `requiredRuns` | `int` | yes |

