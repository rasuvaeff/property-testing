---
title: Обзор API
---

# Обзор API

Эта страница отображает каждый `@api`-тип в `src/` — фактический публичный
контракт, взятый проходом рефлексии (`docs/scripts/reflect-api.php`), а не
то, что случайно упомянуто в README. Каждое имя класса ссылается на
сгенерированную справочную страницу (`docs/scripts/generate-api.mjs`) с его
публичными свойствами и сигнатурами методов из того же прохода рефлексии.

## С чего начать

| Тип | Роль |
|---|---|
| [`Property`](/ru/api/classes/Property) | Атрибут `#[Property]` — `runs`, `seed`, `generators`, `maxShrinks`, `examples`, `maxDiscards`, `timeoutMs`, `budgetMs`. |
| [`Gen`](/ru/api/classes/Gen) | Статическая фабрика всех встроенных генераторов — см. [обзор генераторов](/ru/generators/index). |
| [`Assume`](/ru/api/classes/Assume) | `Assume::that(bool)` — отбросить текущую попытку. |
| [`ArbitraryInterface`](/ru/api/classes/ArbitraryInterface) | Реализуйте его для [своего генератора](/ru/generators/custom-arbitrary). |
| [`Shrinkable`](/ru/api/classes/Shrinkable) | Значение + ленивое shrink-дерево — `leaf()`, `of()`, `map()`. См. [Shrinking](/ru/shrinking). |
| [`CounterExample`](/ru/api/classes/CounterExample) | Данные фальсифицированного прогона — `toArray()`, `toJson()`, `toExamplesCode()`. |
| [`Classify`](/ru/api/classes/Classify) | `label()`, `when()`, `cover()` — см. [Распределение](/ru/distribution). |

## Stateful-тестирование

| Тип | Роль |
|---|---|
| [`Command`](/ru/api/classes/Command) | Интерфейс: `preCondition`/`nextState`/`run`/`postCondition` + `\Stringable`. |
| [`CommandSequence`](/ru/api/classes/CommandSequence) | Сгенерированная, shrink'уемая последовательность команд. |
| [`StateMachine`](/ru/api/classes/StateMachine) | `StateMachine::check()` — прогоняет последовательность против свежей системы. |

См. [State machine: концепции](/ru/state-machine/concepts).

## Падения

Каждое исключение пакета с фактическими `readonly`-полями и геттерами (не
просто упоминание по имени): **[справочник исключений](/ru/api/exceptions)**.

## Публичное, но не то, с чего начинают

Два `@api`-члена существуют для более узкого, продвинутого применения, чем
«написать `#[Property]`-тест»:

- [`Random::__construct(int $seed)`](/ru/api/classes/Random) —
  ручное построение `Random` для проверки собственного `ArbitraryInterface` в
  изоляции, вне запущенного property. Внутри property `Random` всегда
  инжектируется раннером.
- [`Classify::beginRun()` / `flushRun()` / `flushRequirements()`](/ru/api/classes/Classify) —
  runner-lifecycle-хуки, которые `PropertyInterceptor` вызывает для сброса и
  слива буфера распределения текущего прогона. Публичны потому что
  интерцептор живёт в другом namespace (`Internal\`), а не потому что
  тестовый код должен их вызывать.

`AssumptionSkipped` намеренно **отсутствует** на этой карте — он публичный,
но не `@api`; см. заметку внизу
[справочника исключений](/ru/api/exceptions#не-часть-контракта).

<small>Эта страница и [Исключения](/ru/api/exceptions) сверяются
со снимком рефлексии <code>src/</code>, а не поддерживаются по памяти — см.
<a href="https://github.com/rasuvaeff/property-testing/issues/29"
target="_blank" rel="noopener">issue #29</a>, какие пробелы это закрыло.</small>
