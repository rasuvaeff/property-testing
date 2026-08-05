---
title: "Shrinkable"
description: "Shrinkable — class в справочнике API property-testing."
---

<!-- АВТОГЕНЕРАЦИЯ: docs/scripts/generate-api.mjs, проход рефлексии по src/ (docs/scripts/reflect-api.php) — не редактировать вручную. -->

# `Shrinkable`

`Rasuvaeff\PropertyTesting\Shrinkable`

**Класс** — [Исходник](https://github.com/rasuvaeff/property-testing/blob/master/src/Shrinkable.php)

## Свойства

| Свойство | Тип | Readonly |
|---|---|---|
| `value` | `mixed` | yes |

## Методы

### leaf()

```php
static leaf(mixed $value): Shrinkable
```

- `$value` — undefined

### of()

```php
static of(mixed $value, Closure $shrinks): Shrinkable
```

- `$value` — undefined
- `$shrinks` — undefined

### shrinks()

```php
shrinks(): iterable
```

### map()

```php
map(Closure $map): Shrinkable
```

- `$map` — undefined

