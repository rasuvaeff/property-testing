---
title: "Property"
description: "Marks a test method as a property: the PropertyInterceptor takes over, generating random arguments from a generators method until the property has…"
---

<!-- АВТОГЕНЕРАЦИЯ: docs/scripts/generate-api.mjs, проход рефлексии по src/ (docs/scripts/reflect-api.php) — не редактировать вручную. -->

# `Property`

`Rasuvaeff\PropertyTesting\Property`

**Класс** — [Исходник](https://github.com/rasuvaeff/property-testing/blob/master/src/Property.php)

*Текст ниже — на английском, из PHPDoc в исходном коде.*

Marks a test method as a property: the PropertyInterceptor takes over,
generating random arguments from a generators method until the property has
completed $runs successful checks or exhausted its discard budget.

Attribute arguments in PHP must be constant expressions, so the generators
cannot be passed inline. Instead name a method (on the same test case) that
returns `array<string, ArbitraryInterface>`, keyed by parameter name. When
$generators is null the runner falls back to a method named
`<testMethod>Generators`.

## Свойства

| Свойство | Тип | Readonly |
|---|---|---|
| `runs` | `int` | yes |
| `seed` | `?int` | yes |
| `generators` | `?string` | yes |
| `maxShrinks` | `?int` | yes |
| `examples` | `?string` | yes |
| `maxDiscards` | `?int` | yes |
| `timeoutMs` | `?int` | yes |
| `budgetMs` | `?int` | yes |

