---
title: "PostconditionViolation"
---

<!-- АВТОГЕНЕРАЦИЯ: docs/scripts/generate-api.mjs, проход рефлексии по src/ (docs/scripts/reflect-api.php) — не редактировать вручную. -->

# `PostconditionViolation`

`Rasuvaeff\PropertyTesting\StateMachine\PostconditionViolation`

**Класс** — [Исходник](https://github.com/rasuvaeff/property-testing/blob/master/src/StateMachine/PostconditionViolation.php) — **Наследует:** `RuntimeException`

## Свойства

| Свойство | Тип | Readonly |
|---|---|---|
| `trace` | `array` | yes |
| `step` | `int` | yes |
| `command` | `StateMachine\Command` | yes |
| `model` | `mixed` | yes |
| `result` | `mixed` | yes |

