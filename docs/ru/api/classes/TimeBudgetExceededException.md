---
title: "TimeBudgetExceededException"
description: "Thrown (as the failure of a property) when the random phase's wall-clock time exceeds the Property::$budgetMs budget before the requested number of…"
---

<!-- АВТОГЕНЕРАЦИЯ: docs/scripts/generate-api.mjs, проход рефлексии по src/ (docs/scripts/reflect-api.php) — не редактировать вручную. -->

# `TimeBudgetExceededException`

`Rasuvaeff\PropertyTesting\TimeBudgetExceededException`

**Класс** — [Исходник](https://github.com/rasuvaeff/property-testing/blob/master/src/TimeBudgetExceededException.php) — **Наследует:** `RuntimeException`

*Текст ниже — на английском, из PHPDoc в исходном коде.*

Thrown (as the failure of a property) when the random phase's wall-clock
time exceeds the Property::$budgetMs budget before the requested
number of successful checks completes. It exposes the completed and required
run counts so a slow property cannot silently check less than it claims.

The fix is to raise the budget, lower the run count, or speed up the
property body (often by narrowing the generators).

## Свойства

| Свойство | Тип | Readonly |
|---|---|---|
| `propertyName` | `string` | yes |
| `budgetMs` | `int` | yes |
| `elapsedMs` | `float` | yes |
| `successfulRuns` | `int` | yes |
| `requiredRuns` | `int` | yes |

