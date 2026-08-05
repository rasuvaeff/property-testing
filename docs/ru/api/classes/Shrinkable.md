---
title: "Shrinkable"
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

| Метод |
|---|
| `static leaf(mixed $value): Shrinkable` |
| `static of(mixed $value, Closure $shrinks): Shrinkable` |
| `shrinks(): iterable` |
| `map(Closure $map): Shrinkable` |

