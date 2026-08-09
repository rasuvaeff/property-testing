---
title: "PropertyInterceptor"
description: "Runs a Property: generates random arguments, executes the test body until Property::$runs successful checks complete, and on the first failure shrinks…"
---

<!-- АВТОГЕНЕРАЦИЯ: docs/scripts/generate-api.mjs, проход рефлексии по src/ (docs/scripts/reflect-api.php) — не редактировать вручную. -->

# `PropertyInterceptor`

`Rasuvaeff\PropertyTesting\Internal\PropertyInterceptor`

**Класс** — [Исходник](https://github.com/rasuvaeff/property-testing/blob/master/src/Internal/PropertyInterceptor.php)

*Текст ниже — на английском, из PHPDoc в исходном коде.*

Runs a Property: generates random arguments, executes the test body
until Property::$runs successful checks complete, and on the first
failure shrinks the counterexample to a minimal one.

The interceptor self-registers via the Property attribute's
\Testo\Pipeline\Attribute\FallbackInterceptor, so simply requiring the
package is enough — no plugin registration in testo.php is needed.

It sits close to the test function in the pipeline (after data providers,
repeat and retry policies) so it owns argument generation for property tests.

## Методы

### runTest()

```php
runTest(
    Testo\Core\Context\TestInfo $info,
    callable $next,
): Testo\Core\Context\TestResult
```

