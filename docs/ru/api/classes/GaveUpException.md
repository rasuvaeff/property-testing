---
title: "GaveUpException"
description: "Thrown (as the failure of a property) when discarded inputs exceed the configured budget before the requested number of successful checks completes."
---

<!-- АВТОГЕНЕРАЦИЯ: docs/scripts/generate-api.mjs, проход рефлексии по src/ (docs/scripts/reflect-api.php) — не редактировать вручную. -->

# `GaveUpException`

`Rasuvaeff\PropertyTesting\GaveUpException`

**Класс** — [Исходник](https://github.com/rasuvaeff/property-testing/blob/master/src/GaveUpException.php) — **Наследует:** `RuntimeException`

*Текст ниже — на английском, из PHPDoc в исходном коде.*

Thrown (as the failure of a property) when discarded inputs exceed the
configured budget before the requested number of successful checks completes.
It exposes successful, discarded and total attempt counts so the result cannot
hide a weak input distribution.

The fix is almost always to construct valid inputs directly (e.g.
Gen::flatMap() / Gen::draw()) rather than generating broadly and
discarding, so runs are valid by construction.

## Свойства

| Свойство | Тип | Readonly |
|---|---|---|
| `propertyName` | `string` | yes |
| `requiredRuns` | `int` | yes |
| `successfulRuns` | `int` | yes |
| `discardedRuns` | `int` | yes |
| `attempts` | `int` | yes |
| `maxDiscards` | `int` | yes |

