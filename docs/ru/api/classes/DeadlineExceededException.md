---
title: "DeadlineExceededException"
description: "Thrown (as the failure of a property) when a single run's body takes longer than the Property::$timeoutMs deadline."
---

<!-- АВТОГЕНЕРАЦИЯ: docs/scripts/generate-api.mjs, проход рефлексии по src/ (docs/scripts/reflect-api.php) — не редактировать вручную. -->

# `DeadlineExceededException`

`Rasuvaeff\PropertyTesting\DeadlineExceededException`

**Класс** — [Исходник](https://github.com/rasuvaeff/property-testing/blob/master/src/DeadlineExceededException.php) — **Наследует:** `RuntimeException`

*Текст ниже — на английском, из PHPDoc в исходном коде.*

Thrown (as the failure of a property) when a single run's body takes longer
than the Property::$timeoutMs deadline. The offending input is the
counterexample: it is pathological for the code under test (catastrophic
regex, deep recursion, unbounded backoff) — or the deadline is too tight.

The input is reported as-is, NOT shrunk: shrink acceptance would have to
re-measure wall time, and timing noise makes that descent non-deterministic.

## Свойства

| Свойство | Тип | Readonly |
|---|---|---|
| `propertyName` | `string` | yes |
| `arguments` | `array` | yes |
| `elapsedMs` | `float` | yes |
| `timeoutMs` | `int` | yes |

