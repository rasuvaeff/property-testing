---
title: Exceptions
---

# Exceptions

Every failure mode is a typed exception with `readonly` fields or getters —
generated from `ReflectionClass` over every `@api` type in `src/`, not
transcribed from prose. The README mentions these by name; this page is the
field-by-field reference.

| Exception | Thrown when | Fields / getters |
|---|---|---|
| [`PropertyViolationException`](/en/api/classes/PropertyViolationException) | A random or example run falsifies the property. | `getCounterExample(): `[`CounterExample`](/en/api/classes/CounterExample) |
| [`RegressionViolationException`](/en/api/classes/RegressionViolationException) | A replayed **values** entry from the [regression corpus](/en/regression-corpus) still fails. | `getArguments(): array`, `getSeed(): int` |
| [`ExampleViolationException`](/en/api/classes/ExampleViolationException) | An [explicit example](/en/explicit-examples) fails. Reported verbatim — never shrunk. | `getIndex(): int` (position in the examples list), `getArguments(): array` |
| [`GaveUpException`](/en/api/classes/GaveUpException) | Discarded attempts ([`Assume::that()`](/en/controlling-runs/assume-vs-filter)) exceed `maxDiscards` before `runs` successful checks complete. | `$propertyName: string`, `$requiredRuns: int`, `$successfulRuns: int`, `$discardedRuns: int`, `$attempts: int`, `$maxDiscards: int` |
| [`DeadlineExceededException`](/en/api/classes/DeadlineExceededException) | A single run exceeds `timeoutMs`. Reported as-is — not shrunk. | `$propertyName: string`, `$arguments: array`, `$elapsedMs: float`, `$timeoutMs: int` |
| [`TimeBudgetExceededException`](/en/api/classes/TimeBudgetExceededException) | The whole random phase exceeds `budgetMs` before `runs` successful checks complete. | `$propertyName: string`, `$budgetMs: int`, `$elapsedMs: float`, `$successfulRuns: int`, `$requiredRuns: int` |
| [`GenerationExhausted`](/en/api/classes/GenerationExhausted) | `Gen::filter()` rejects 100 candidates in a row, or a sized collection (`uniqueArrayOf`/`dictOf`/`commands`) cannot reach its `$min` within its attempt budget. | `$arbitrary: string`, `$attempts: int` |
| [`CoverageViolationException`](/en/api/classes/CoverageViolationException) | [`Classify::cover()`](/en/distribution) requirement not met — even though every run passed. | Marker exception, no extra fields. |
| [`PostconditionViolation`](/en/api/classes/PostconditionViolation) | A [`Command::postCondition()`](/en/state-machine/concepts) returns `false` (or throws) during `StateMachine::check()`. | `$trace: array`, `$step: int`, `$command: Command`, `$model: mixed`, `$result: mixed` |

## Not part of the contract

[`AssumptionSkipped`](https://github.com/rasuvaeff/property-testing/blob/master/src/AssumptionSkipped.php)
is a public class in the package's root namespace, but it carries **no**
`@api` tag: it's the internal control-flow signal `Assume::that(false)`
throws to abort the current attempt, caught by the interceptor before it
ever reaches your test. Don't catch it, and don't build on it — a future
release is free to change how discards are signalled internally.

<small>Generated from a reflection pass over every `@api` type in `src/`
(<code>docs/scripts/reflect-api.php</code>) — see [API overview](/en/api/) for how this stays in sync with the source.</small>
