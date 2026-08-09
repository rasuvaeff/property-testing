---
title: "PostconditionViolation"
description: "Thrown by StateMachine::check() when a command's Command::postCondition() returns false."
---

<!-- АВТОГЕНЕРАЦИЯ: docs/scripts/generate-api.mjs, проход рефлексии по src/ (docs/scripts/reflect-api.php) — не редактировать вручную. -->

# `PostconditionViolation`

`Rasuvaeff\PropertyTesting\StateMachine\PostconditionViolation`

**Класс** — [Исходник](https://github.com/rasuvaeff/property-testing/blob/master/src/StateMachine/PostconditionViolation.php) — **Наследует:** `RuntimeException`

*Текст ниже — на английском, из PHPDoc в исходном коде.*

Thrown by StateMachine::check() when a command's
Command::postCondition() returns false.

Carries the executed trace (command labels up to and including the failing
one), the failing command, and the pre-state model and observed result, so
the property runner surfaces exactly which step of the sequence broke.

## Свойства

| Свойство | Тип | Readonly |
|---|---|---|
| `trace` | `array` | yes |
| `step` | `int` | yes |
| `command` | `StateMachine\Command` | yes |
| `model` | `mixed` | yes |
| `result` | `mixed` | yes |

