---
title: "FilteredArbitrary"
description: "Generates values from a delegate arbitrary, retrying until a predicate holds."
---

<!-- АВТОГЕНЕРАЦИЯ: docs/scripts/generate-api.mjs, проход рефлексии по src/ (docs/scripts/reflect-api.php) — не редактировать вручную. -->

# `FilteredArbitrary`

`Rasuvaeff\PropertyTesting\Arbitrary\FilteredArbitrary`

**Класс** — [Исходник](https://github.com/rasuvaeff/property-testing/blob/master/src/Arbitrary/FilteredArbitrary.php)

*Текст ниже — на английском, из PHPDoc в исходном коде.*

Generates values from a delegate arbitrary, retrying until a predicate holds.

Filtering is bounded: after self::MAX_ATTEMPTS consecutive rejections
the generator throws GenerationExhausted rather than yield a value that
fails the predicate — a property never receives an out-of-domain input. Use
\Rasuvaeff\PropertyTesting\Assume::that() inside the property when the
rejection rate is high, which skips discarded runs cleanly, or
\Rasuvaeff\PropertyTesting\Gen::flatMap() to construct dependent values
without filtering at all.

Shrinking walks the inner value's tree, keeping only branches whose value
satisfies the predicate (a rejected candidate's subtree is pruned with it).

## Методы

### generate()

```php
generate(Random $random): Shrinkable
```

