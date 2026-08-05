---
title: "Command"
---

<!-- АВТОГЕНЕРАЦИЯ: docs/scripts/generate-api.mjs, проход рефлексии по src/ (docs/scripts/reflect-api.php) — не редактировать вручную. -->

# `Command`

`Rasuvaeff\PropertyTesting\StateMachine\Command`

**Интерфейс** — [Исходник](https://github.com/rasuvaeff/property-testing/blob/master/src/StateMachine/Command.php)

## Методы

| Метод |
|---|
| `preCondition(mixed $model): bool` |
| `nextState(mixed $model): mixed` |
| `run(mixed $model, mixed $system): mixed` |
| `postCondition(mixed $model, mixed $result): bool` |

