---
title: "StateMachine"
description: "Drives a CommandSequence against a fresh system under test for stateful / model-based testing."
---

<!-- АВТОГЕНЕРАЦИЯ: docs/scripts/generate-api.mjs, проход рефлексии по src/ (docs/scripts/reflect-api.php) — не редактировать вручную. -->

# `StateMachine`

`Rasuvaeff\PropertyTesting\StateMachine\StateMachine`

**Класс** — [Исходник](https://github.com/rasuvaeff/property-testing/blob/master/src/StateMachine/StateMachine.php)

*Текст ниже — на английском, из PHPDoc в исходном коде.*

Drives a CommandSequence against a fresh system under test for
stateful / model-based testing.

Call it from a property body with the generated sequence and a factory that
builds a fresh system per run:

```php
#[Property]
public function stackMatchesModel(CommandSequence $sequence): void
{
    StateMachine::check($sequence, static fn(): Stack => new Stack());
}
```

For each command it re-checks Command::preCondition() against the
running model and skips the command if it no longer holds — shrinking may have
dropped an earlier step that a later precondition depended on, so a replayed
sequence stays sound without the arbitrary re-validating every candidate. A
passing precondition runs the command, asserts Command::postCondition()
(throwing PostconditionViolation on failure), then advances the model.

## Методы

### check()

```php
static check(StateMachine\CommandSequence $sequence, Closure $system): void
```

