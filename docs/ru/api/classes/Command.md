---
title: "Command"
description: "Command — interface в справочнике API property-testing."
---

<!-- АВТОГЕНЕРАЦИЯ: docs/scripts/generate-api.mjs, проход рефлексии по src/ (docs/scripts/reflect-api.php) — не редактировать вручную. -->

# `Command`

`Rasuvaeff\PropertyTesting\StateMachine\Command`

**Интерфейс** — [Исходник](https://github.com/rasuvaeff/property-testing/blob/master/src/StateMachine/Command.php)

## Методы

### preCondition()

```php
preCondition(mixed $model): bool
```

- `$model` — undefined

### nextState()

```php
nextState(mixed $model): mixed
```

- `$model` — undefined

### run()

```php
run(mixed $model, mixed $system): mixed
```

- `$model` — undefined
- `$system` — undefined

### postCondition()

```php
postCondition(mixed $model, mixed $result): bool
```

- `$model` — undefined
- `$result` — undefined

