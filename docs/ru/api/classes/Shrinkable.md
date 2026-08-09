---
title: "Shrinkable"
description: "A generated value together with a lazy tree of progressively \"smaller\" variants of it — the unit of integrated shrinking."
---

<!-- АВТОГЕНЕРАЦИЯ: docs/scripts/generate-api.mjs, проход рефлексии по src/ (docs/scripts/reflect-api.php) — не редактировать вручную. -->

# `Shrinkable`

`Rasuvaeff\PropertyTesting\Shrinkable`

**Класс** — [Исходник](https://github.com/rasuvaeff/property-testing/blob/master/src/Shrinkable.php)

*Текст ниже — на английском, из PHPDoc в исходном коде.*

A generated value together with a lazy tree of progressively "smaller"
variants of it — the unit of integrated shrinking.

Every ArbitraryInterface::generate() call returns one of these. The
property runner reads $value, and when the property fails it walks
shrinks(): each child is a smaller candidate that carries its own
subtree, so accepting a candidate immediately provides the next round of
even smaller candidates. Because the tree is built at generation time, a
transformed arbitrary (Gen::map(), Gen::flatMap()) shrinks in
the source domain and re-applies the transformation — no inverse function
is ever needed.

Children are produced lazily (the closure runs only when the runner asks),
so building a node costs nothing until shrinking actually happens.

## Свойства

| Свойство | Тип | Readonly |
|---|---|---|
| `value` | `mixed` | yes |

## Методы

### leaf()

```php
static leaf(mixed $value): Shrinkable
```

A value with no smaller variants (terminal node).

### of()

```php
static of(mixed $value, Closure $shrinks): Shrinkable
```

A value with lazily-computed smaller variants, ordered most aggressive
first (typically toward a zero/empty/identity element).

### shrinks()

```php
shrinks(): iterable
```

The smaller variants of this value, each with its own subtree.

### map()

```php
map(Closure $map): Shrinkable
```

Transform the whole tree through a pure function: the value and, lazily,
every shrink candidate. This is what makes Gen::map() shrink.

