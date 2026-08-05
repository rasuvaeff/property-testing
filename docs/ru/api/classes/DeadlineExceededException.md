---
title: "DeadlineExceededException"
description: "DeadlineExceededException — class в справочнике API property-testing."
---

<!-- АВТОГЕНЕРАЦИЯ: docs/scripts/generate-api.mjs, проход рефлексии по src/ (docs/scripts/reflect-api.php) — не редактировать вручную. -->

# `DeadlineExceededException`

`Rasuvaeff\PropertyTesting\DeadlineExceededException`

**Класс** — [Исходник](https://github.com/rasuvaeff/property-testing/blob/master/src/DeadlineExceededException.php) — **Наследует:** `RuntimeException`

## Свойства

| Свойство | Тип | Readonly |
|---|---|---|
| `propertyName` | `string` | yes |
| `arguments` | `array` | yes |
| `elapsedMs` | `float` | yes |
| `timeoutMs` | `int` | yes |

