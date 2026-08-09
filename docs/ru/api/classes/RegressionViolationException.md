---
title: "RegressionViolationException"
description: "Reported when a recorded regression fails again: the minimised input of an earlier failure, replayed from the on-disk corpus (`PROPERTY_DB`) before the…"
---

<!-- АВТОГЕНЕРАЦИЯ: docs/scripts/generate-api.mjs, проход рефлексии по src/ (docs/scripts/reflect-api.php) — не редактировать вручную. -->

# `RegressionViolationException`

`Rasuvaeff\PropertyTesting\RegressionViolationException`

**Класс** — [Исходник](https://github.com/rasuvaeff/property-testing/blob/master/src/RegressionViolationException.php) — **Наследует:** `RuntimeException`

*Текст ниже — на английском, из PHPDoc в исходном коде.*

Reported when a recorded regression fails again: the minimised input of an
earlier failure, replayed from the on-disk corpus (`PROPERTY_DB`) before the
random phase.

The input is already minimal — it is the shrunk counterexample of the run that
recorded it — so it is replayed once and reported verbatim, without shrinking.
A regression stored as a bare seed instead of values replays the whole random
phase and reports the usual PropertyViolationException.

## Методы

### getArguments()

```php
getArguments(): array
```

### getSeed()

```php
getSeed(): int
```

