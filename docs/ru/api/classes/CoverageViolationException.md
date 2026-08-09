---
title: "CoverageViolationException"
description: "Thrown (as the failure of an otherwise passing property) when a coverage requirement registered via Classify::cover() is not met: the property held on…"
---

<!-- АВТОГЕНЕРАЦИЯ: docs/scripts/generate-api.mjs, проход рефлексии по src/ (docs/scripts/reflect-api.php) — не редактировать вручную. -->

# `CoverageViolationException`

`Rasuvaeff\PropertyTesting\CoverageViolationException`

**Класс** — [Исходник](https://github.com/rasuvaeff/property-testing/blob/master/src/CoverageViolationException.php) — **Наследует:** `RuntimeException`

*Текст ниже — на английском, из PHPDoc в исходном коде.*

Thrown (as the failure of an otherwise passing property) when a coverage
requirement registered via Classify::cover() is not met: the property
held on every run, but the generators did not exercise a labelled case often
enough, so the pass would be (partially) vacuous.

Нет публичных свойств или методов кроме конструктора.

