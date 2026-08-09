---
title: "GenerationExhausted"
description: "Thrown when a bounded-attempt generator cannot produce a value that satisfies its constraint within its attempt budget:…"
---

<!-- АВТОГЕНЕРАЦИЯ: docs/scripts/generate-api.mjs, проход рефлексии по src/ (docs/scripts/reflect-api.php) — не редактировать вручную. -->

# `GenerationExhausted`

`Rasuvaeff\PropertyTesting\GenerationExhausted`

**Класс** — [Исходник](https://github.com/rasuvaeff/property-testing/blob/master/src/GenerationExhausted.php) — **Наследует:** `RuntimeException`

*Текст ниже — на английском, из PHPDoc в исходном коде.*

Thrown when a bounded-attempt generator cannot produce a value that satisfies
its constraint within its attempt budget: \Rasuvaeff\PropertyTesting\Arbitrary\FilteredArbitrary
whose predicate rejected every draw, or a sized collection
(\Rasuvaeff\PropertyTesting\Arbitrary\DictionaryArbitrary,
\Rasuvaeff\PropertyTesting\Arbitrary\UniqueArrayArbitrary,
\Rasuvaeff\PropertyTesting\Arbitrary\CommandSequenceArbitrary) that
could not reach its declared minimum.

A generator NEVER yields a value outside its declared domain — it fails loudly
with this exception instead, so a property never silently receives an
out-of-domain input. Exhaustion can be transient (a satisfiable-but-rare
predicate) or structural (a domain too small to ever meet the minimum); the
message describes what happened and how to widen the domain.

## Свойства

| Свойство | Тип | Readonly |
|---|---|---|
| `arbitrary` | `string` | yes |
| `attempts` | `int` | yes |

