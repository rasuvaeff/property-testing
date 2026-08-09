---
title: "Random"
description: "Seedable, deterministic pseudo-random number generator."
---

<!-- АВТОГЕНЕРАЦИЯ: docs/scripts/generate-api.mjs, проход рефлексии по src/ (docs/scripts/reflect-api.php) — не редактировать вручную. -->

# `Random`

`Rasuvaeff\PropertyTesting\Random`

**Класс** — [Исходник](https://github.com/rasuvaeff/property-testing/blob/master/src/Random.php)

*Текст ниже — на английском, из PHPDoc в исходном коде.*

Seedable, deterministic pseudo-random number generator.

Two instances created with the same seed produce identical sequences, which
is what makes counterexamples reproducible. Backed by an object-scoped
Mersenne Twister (MT19937) engine via ext-random's Randomizer, so it
is independent of PHP's global mt_rand state — important inside a test runner
where other code may draw random numbers between runs.

## Методы

### int()

```php
int(int $min, int $max): int
```

Uniform integer in the inclusive range [$min, $max].

### float()

```php
float(): float
```

Uniform float in the half-open range [0.0, 1.0).

### bytes()

```php
bytes(int $length): string
```

Random byte string of the given length (bytes in 0..255).

