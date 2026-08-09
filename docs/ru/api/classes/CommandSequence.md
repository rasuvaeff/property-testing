---
title: "CommandSequence"
description: "A generated, valid-by-construction sequence of Commands together with the initial model it was generated against."
---

<!-- АВТОГЕНЕРАЦИЯ: docs/scripts/generate-api.mjs, проход рефлексии по src/ (docs/scripts/reflect-api.php) — не редактировать вручную. -->

# `CommandSequence`

`Rasuvaeff\PropertyTesting\StateMachine\CommandSequence`

**Класс** — [Исходник](https://github.com/rasuvaeff/property-testing/blob/master/src/StateMachine/CommandSequence.php)

*Текст ниже — на английском, из PHPDoc в исходном коде.*

A generated, valid-by-construction sequence of Commands together with
the initial model it was generated against.

This is the value a \Rasuvaeff\PropertyTesting\Gen::commands() arbitrary
produces; the property body hands it to StateMachine::check(). It is
\Stringable so a falsified property renders the failing sequence as a
readable trace instead of `[N element(s)]`.

## Свойства

| Свойство | Тип | Readonly |
|---|---|---|
| `initialModel` | `mixed` | yes |
| `commands` | `array` | yes |

## Методы

### __toString()

```php
__toString(): string
```

