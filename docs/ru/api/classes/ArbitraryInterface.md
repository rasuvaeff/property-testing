---
title: "ArbitraryInterface"
description: "Describes a space of random values with integrated shrinking."
---

<!-- АВТОГЕНЕРАЦИЯ: docs/scripts/generate-api.mjs, проход рефлексии по src/ (docs/scripts/reflect-api.php) — не редактировать вручную. -->

# `ArbitraryInterface`

`Rasuvaeff\PropertyTesting\ArbitraryInterface`

**Интерфейс** — [Исходник](https://github.com/rasuvaeff/property-testing/blob/master/src/ArbitraryInterface.php)

*Текст ниже — на английском, из PHPDoc в исходном коде.*

Describes a space of random values with integrated shrinking.

An arbitrary produces a Shrinkable via generate(): the
generated value together with a lazy tree of smaller candidates, each
carrying its own subtree. The property runner reads the value, and on a
failure descends through the tree, accepting the first candidate that still
fails, until no smaller value fails — yielding a minimal counterexample.

Because shrink candidates are attached at generation time, combinators such
as Gen::map() and Gen::flatMap() shrink in the source domain
and re-apply their transformation; implementations never need to invert a
transformed value.

## Методы

### generate()

```php
generate(Random $random): Shrinkable
```

Produce one random value from this arbitrary's space, together with its
shrink tree. Candidates must be ordered most aggressive first (typically
toward a zero/empty/identity element) and every branch of the tree must
be finite, so shrinking terminates.

