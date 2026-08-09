---
title: "CommandSequenceArbitrary"
description: "Generates valid Command sequences for stateful / model-based testing and shrinks them by dropping and simplifying steps."
---

<!-- АВТОГЕНЕРАЦИЯ: docs/scripts/generate-api.mjs, проход рефлексии по src/ (docs/scripts/reflect-api.php) — не редактировать вручную. -->

# `CommandSequenceArbitrary`

`Rasuvaeff\PropertyTesting\Arbitrary\CommandSequenceArbitrary`

**Класс** — [Исходник](https://github.com/rasuvaeff/property-testing/blob/master/src/Arbitrary/CommandSequenceArbitrary.php)

*Текст ниже — на английском, из PHPDoc в исходном коде.*

Generates valid Command sequences for stateful / model-based testing
and shrinks them by dropping and simplifying steps.

Generation is model-aware: at each step a command generator is drawn at
random, and the produced command is appended only if its
Command::preCondition() holds in the running model, which is then
advanced via Command::nextState(). The sequence is therefore valid by
construction; if no applicable command is found within a bounded number of
attempts the sequence stops early.

Shrinking removes whole blocks of commands (most aggressive first, down to a
single command so a failing step in the middle can be isolated) and then
simplifies individual commands through their own shrink trees. Dropped or
simplified sequences are not re-validated here — \Rasuvaeff\PropertyTesting\StateMachine\StateMachine::check()
skips any command whose precondition a change invalidated, keeping every
candidate sound.

## Методы

### generate()

```php
generate(Random $random): Shrinkable
```

