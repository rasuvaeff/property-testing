---
title: Исключения
---

# Исключения

Каждый сценарий падения — типизированное исключение с `readonly`-полями или
геттерами, сгенерированное из `ReflectionClass` по всем `@api`-типам в
`src/`, а не переписанное из прозы. README называет их по имени; эта страница
— справочник по полям.

| Исключение | Когда бросается | Поля / геттеры |
|---|---|---|
| [`PropertyViolationException`](/ru/api/classes/PropertyViolationException) | Случайный или explicit-прогон фальсифицировал property. | `getCounterExample(): `[`CounterExample`](/ru/api/classes/CounterExample) |
| [`RegressionViolationException`](/ru/api/classes/RegressionViolationException) | Реплей **values**-записи из [корпуса регрессий](/ru/regression-corpus) всё ещё падает. | `getArguments(): array`, `getSeed(): int` |
| [`ExampleViolationException`](/ru/api/classes/ExampleViolationException) | Падает [явный пример](/ru/explicit-examples). Сообщается как есть — без shrink'а. | `getIndex(): int` (позиция в списке примеров), `getArguments(): array` |
| [`GaveUpException`](/ru/api/classes/GaveUpException) | Число отброшенных попыток ([`Assume::that()`](/ru/controlling-runs/assume-vs-filter)) превысило `maxDiscards` до набора `runs` успешных проверок. | `$propertyName: string`, `$requiredRuns: int`, `$successfulRuns: int`, `$discardedRuns: int`, `$attempts: int`, `$maxDiscards: int` |
| [`DeadlineExceededException`](/ru/api/classes/DeadlineExceededException) | Один прогон превысил `timeoutMs`. Сообщается как есть — без shrink'а. | `$propertyName: string`, `$arguments: array`, `$elapsedMs: float`, `$timeoutMs: int` |
| [`TimeBudgetExceededException`](/ru/api/classes/TimeBudgetExceededException) | Вся random-фаза превысила `budgetMs` до набора `runs` успешных проверок. | `$propertyName: string`, `$budgetMs: int`, `$elapsedMs: float`, `$successfulRuns: int`, `$requiredRuns: int` |
| [`GenerationExhausted`](/ru/api/classes/GenerationExhausted) | `Gen::filter()` отверг 100 кандидатов подряд, либо sized-коллекция (`uniqueArrayOf`/`dictOf`/`commands`) не смогла достичь `$min` в рамках бюджета попыток. | `$arbitrary: string`, `$attempts: int` |
| [`CoverageViolationException`](/ru/api/classes/CoverageViolationException) | Не выполнено требование [`Classify::cover()`](/ru/distribution) — даже если все прогоны прошли. | Маркерное исключение, без дополнительных полей. |
| [`PostconditionViolation`](/ru/api/classes/PostconditionViolation) | [`Command::postCondition()`](/ru/state-machine/concepts) вернул `false` (или бросил) во время `StateMachine::check()`. | `$trace: array`, `$step: int`, `$command: Command`, `$model: mixed`, `$result: mixed` |

## Не часть контракта

[`AssumptionSkipped`](https://github.com/rasuvaeff/property-testing/blob/master/src/AssumptionSkipped.php)
— публичный класс в корневом namespace пакета, но **без** тега `@api`: это
внутренний сигнал control-flow, который бросает `Assume::that(false)` для
прерывания текущей попытки, перехватываемый интерцептором до того, как
доберётся до вашего теста. Не ловите его и не стройте на нём логику — в
будущем релизе способ сигнализировать об отбрасывании может измениться внутренне.

<small>Сгенерировано проходом рефлексии по всем `@api`-типам в `src/`
(<code>docs/scripts/reflect-api.php</code>) — как это остаётся синхронным с
исходниками, см. [обзор API](/ru/api/).</small>
