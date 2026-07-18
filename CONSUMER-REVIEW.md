# Consumer review

Дата оценки: 2026-07-18.

## Статус реализации (ветка `fix/correctness-2.5.0`, релиз 2.5.0)

Все 5 подтверждённых проблем исправлены. `composer build` зелёный (627 тестов),
package-audit чистый, bc-check vs v2.4.0 — без BC-поломок.

| # | Проблема | Статус |
|---|---|---|
| 1 | `Gen::filter()` возвращает значение мимо predicate | ✅ бросает новый `GenerationExhausted`; интерсептор ловит на этапе генерации |
| 2 | 0 успешных проверок = passed | ✅ `runs` означает успешные проверки; discard повторяется до `maxDiscards`, затем структурированный `GaveUpException` |
| 3 | Counterexample прячет структуры | ✅ рекурсивный `Internal\ValueRenderer` + `CounterExample::toArray()`/`toJson()`/`toExamplesCode()` |
| 4 | `min*` не всегда минимум | ✅ `dictOf` (distinct keys) / `commands` гарантируют min или бросают `GenerationExhausted`; `uniqueArrayOf` переведён на тот же тип |
| 5 | `#[Property]` заявляет `TARGET_FUNCTION` | ✅ убран |

Идеи (дженерики, deadline, corpus, provider-ergonomics и т.д.) вынесены в
`yii3-package-plans/property-testing-roadmap.md` (релизы 2.6.0+). Единая модель
исчерпания генерации (идея 2) реализована как `GenerationExhausted`.

### Follow-up находки (2026-07-18)

| Находка | Статус |
|---|---|
| Renderer не раскрывал private/protected DTO-поля (`get_object_vars` извне) | ✅ исправлено: `ValueRenderer::objectProperties()` через рефлексию (private/protected/promoted; uninitialized → маркер, static исключены) |
| Строки не экранировались (`"`, `\n`, `\r`, control → многострочный/неоднозначный вывод) | ✅ исправлено: `ValueRenderer::escape()` (addcslashes control+quote+backslash; multibyte не трогается) |
| Полная discard-модель (`runs` успешных проверок + discard budget) | ✅ `maxDiscards` (default `runs * 10`), в failure видны required/successful/discarded/attempts |
| Renderer: map keys, inherited private, bounded Stringable | ✅ ключи и Stringable экранируются/ограничиваются; private-поля читаются по всей иерархии |

MSI 90.7% (гейт 90); доминирующие выжившие — задокументированные pre-existing
эквиваленты `Gen.php`/`RegexCompiler`.

## Резюме

`rasuvaeff/property-testing` уже выглядит зрелым и заметно сильнее типичных
PHP-реализаций property-based testing. Особенно хорошо сделаны integrated
shrinking, композиция генераторов, контроль распределения и stateful testing.

Перед широким использованием стоит исправить два дефекта, способных давать
ложную уверенность в зелёных тестах:

1. `Gen::filter()` может вернуть значение, не прошедшее predicate.
2. Property успешно проходит, даже если `Assume::that()` отбросил все прогоны.

После исправления корректности главный резерв развития находится не в
добавлении новых генераторов, а в типизации, диагностике counterexamples и
единой модели исчерпания генерации.

## Проблемы

### 1. `Gen::filter()` нарушает собственный контракт

**Приоритет: критический.**

После 100 неудачных попыток `FilteredArbitrary::generate()` возвращает последнее
полученное значение без проверки результата predicate:

- [`src/Arbitrary/FilteredArbitrary.php`](src/Arbitrary/FilteredArbitrary.php)
- [`tests/Arbitrary/FilteredArbitraryTest.php`](tests/Arbitrary/FilteredArbitraryTest.php)

Например:

```php
Gen::filter(Gen::constant(0), static fn(int $value): bool => $value > 0);
```

такой генератор вернёт `0`. В property поступает значение за пределами
объявленного домена, хотя пользователь вправе полагаться на predicate.

Рекомендуемое поведение: после исчерпания лимита бросать отдельное исключение,
например `GenerationExhausted`, содержащее число попыток и контекст генератора.
Возврат заведомо недопустимого значения недопустим.

### 2. Ноль успешных проверок считается успешным property

**Приоритет: критический.**

`AssumptionSkipped` увеличивает счётчик пропусков, но после завершения цикла
runner только печатает warning и возвращает `Status::Passed`:

- [`src/Internal/PropertyInterceptor.php`](src/Internal/PropertyInterceptor.php)
- [`tests/Internal/PropertyInterceptorTest.php`](tests/Internal/PropertyInterceptorTest.php)

Следовательно, property с `Assume::that(false)` всегда зелёный. Тело теста не
проверило ни одного значения, но CI сообщает об успехе.

Рекомендуемая модель:

- `runs` означает требуемое число успешных проверок;
- отбрасывания не уменьшают это число;
- отдельный `maxDiscards` или `maxAttempts` ограничивает поиск допустимых
  значений;
- достижение лимита без достаточного числа проверок завершает property статусом
  `gave up` или failure, а не warning;
- в результате явно показываются successful runs, discarded runs и attempts.

### 3. Counterexample скрывает содержимое структурированных значений

**Приоритет: высокий.**

`PropertyViolationException` выводит массив как `[N element(s)]`, а обычный
объект только как имя класса:

- [`src/PropertyViolationException.php`](src/PropertyViolationException.php)
- [`src/ExampleViolationException.php`](src/ExampleViolationException.php)

Это особенно заметно в наиболее типичных properties над списками, деревьями,
records и JSON. Shrinking находит минимальное значение, но пользователь не видит
его содержимое. `PROPERTY_VERBOSE` использует сходное форматирование и не решает
проблему.

Нужен общий рекурсивный renderer со следующими ограничениями:

- ограничение глубины и количества элементов;
- ограничение длины строк;
- корректный вывод special floats и binary strings;
- поддержка enum, `DateTimeInterface`, `Stringable` и простых DTO;
- защита от циклических ссылок;
- возможность получить machine-readable representation;
- команда или готовый фрагмент для переноса shrunk case в `Examples` method.

### 4. `minSize` и `minLength` не всегда являются минимумами

**Приоритет: средний.**

Семантика границ различается между генераторами:

- `dictOf(..., minSize: 5)` может вернуть меньше пяти элементов из-за коллизий
  ключей: [`src/Arbitrary/DictionaryArbitrary.php`](src/Arbitrary/DictionaryArbitrary.php);
- `commands(..., minLength: 5)` может вернуть короткую или пустую
  последовательность, если применимую команду найти не удалось:
  [`src/Arbitrary/CommandSequenceArbitrary.php`](src/Arbitrary/CommandSequenceArbitrary.php);
- `uniqueArrayOf()` при недостижимом minimum бросает исключение:
  [`src/Arbitrary/UniqueArrayArbitrary.php`](src/Arbitrary/UniqueArrayArbitrary.php).

Для потребителя одинаково названные параметры должны иметь одинаковый контракт.
`min*` следует либо гарантировать, либо завершать генерацию понятной ошибкой.
Best-effort поведение лучше выражать отдельным параметром или другим именем.

### 5. `#[Property]` заявляет поддержку функций, но runner их не выполняет

**Приоритет: низкий.**

Атрибут объявлен с `Attribute::TARGET_FUNCTION`, однако interceptor обрабатывает
только `ReflectionMethod` и передаёт функцию дальше без property-логики:

- [`src/Property.php`](src/Property.php)
- [`src/Internal/PropertyInterceptor.php`](src/Internal/PropertyInterceptor.php)

Нужно либо реализовать providers для функций, либо убрать `TARGET_FUNCTION`,
чтобы публичный контракт не обещал неработающий сценарий.

## Сильные стороны

### Integrated shrinking

`Shrinkable` хранит значение вместе с ленивым деревом уменьшений. Благодаря
этому `map()` и `flatMap()` сохраняют shrinking исходного домена, а custom
arbitrary имеет один цельный контракт вместо раздельных generate/shrink API.

### Композиция генераторов

Встроенный каталог покрывает примитивы, коллекции, tuples, records, dependent
generation, recursion, regex subset, JSON, binary data, UUID, даты и сетевые
строки. `flatMap()` и `Gen::draw()` позволяют строить зависимые значения без
массовых discards.

### Поиск граничных случаев

Boundary-biased numeric generation практичнее чистого равномерного
распределения. Shrinking целых чисел хорошо подходит для поиска точной границы
монотонного дефекта.

### Воспроизводимость

Есть фиксированные seeds, `PROPERTY_SEED`, подробный failure carrier,
explicit examples и opt-in replay последнего failing seed через `PROPERTY_DB`.
Генераторы используют отдельный `Randomizer`, не зависящий от глобального
`mt_rand` состояния.

### Контроль распределения

`Classify::label()` и `Classify::cover()` позволяют увидеть, какие классы входов
реально были проверены. Coverage gate способен превратить плохое распределение
в failure, что отсутствует во многих аналогах.

### Stateful testing

Модель `Command` + `CommandSequence` + `StateMachine::check()` позволяет искать
ошибки, возникающие только в последовательностях операций. Для PHP-экосистемы
это существенное конкурентное преимущество.

### Интеграция и документация

- Плагин самостоятельно регистрируется через атрибут Testo.
- README подробно описывает shrinking, dependent generation и ограничения.
- Есть `llms.txt`, runnable examples, changelog, roadmap и upgrade guide.
- Публичные исключения предоставляют структурированный доступ к failure и
  counterexample.
- Поддерживаются Testo `^0.10.25` и `^1.0`.

### Инженерное качество

На момент оценки полный `composer build` прошёл успешно:

- Composer validate и normalize: успешно;
- composer-require-checker: неизвестных символов нет;
- PHP CS Fixer: нарушений нет;
- Psalm: ошибок нет, inferred types 99.7604%;
- Testo: 566 тестов, 270388 assertions, все прошли.

Docker выводил предупреждение Git о `dubious ownership` и из-за этого Composer
не смог определить root package version, но gate завершился с кодом `0`.

## Ограничения для потребителя

- Пакет работает только с Testo; адаптеров для PHPUnit и Pest нет.
- Требуется PHP 8.3+ и `ext-mbstring`, что сужает adoption для существующих
  проектов.
- Генераторы и state-machine API широко используют `mixed`; многие ошибки
  custom generators обнаруживаются только во время выполнения.
- Providers приходится делать публичными из-за reflection-only вызова и
  поведения Rector, что загрязняет поверхность тест-класса.
- `Classify` и `DrawContext` используют process-local static state и опираются
  на последовательное выполнение properties.
- Shrinking является greedy и best-effort, а не гарантированно глобально
  минимальным.
- Regression DB хранит только один seed, а не corpus минимальных примеров.
- Replay seed зависит от стабильности алгоритма генератора; после его изменения
  тот же seed может перестать представлять прежний дефект.

## Чего не хватает

### 1. Статической типизации генераторов

Нужны Psalm templates:

```php
/** @template-covariant TValue */
interface ArbitraryInterface
{
    /** @return Shrinkable<TValue> */
    public function generate(Random $random): Shrinkable;
}
```

Типизация должна проходить через `Shrinkable<T>`, `map`, `flatMap`, `filter`,
`tuple`, `arrayOf`, `record`, `elements` и `oneOf`. Stateful API также выиграет
от `Command<TModel, TSystem, TResult>` и типизированного `CommandSequence`.

Это даст потребителю автодополнение, проверку closure signatures и раннее
обнаружение несовместимых генераторов.

### 2. Единой модели generation exhaustion

Нужно одно понятие и одно семейство исключений для:

- невозможного `filter()`;
- excessive discards;
- недостижимого minimum в unique collections и dictionaries;
- отсутствия применимой state-machine command;
- ограничений recursive/dependent generation.

Исключение должно сообщать arbitrary, attempts, predicate/domain context и
рекомендацию использовать construction/`flatMap()` вместо rejection.

### 3. Полноценной диагностики counterexamples

Помимо рекурсивного renderer полезны:

- JSON или PHP export минимального значения;
- автоматическая заготовка для `<property>Examples()`;
- diff original против shrunk;
- отдельный shrink trace в verbose режиме;
- отображение числа shrink trials, а не только accepted steps.

### 4. Deadline и общий execution budget

Нужны timeout на один пример и, возможно, общий budget property. Это защищает
от pathological input в regex, parsers, recursive structures, backoff и
stateful tests. В consumer-сценариях deadline полезнее targeted PBT.

### 5. Автоматический вывод простых генераторов из типов

Опциональный type-driven режим мог бы покрыть:

- `int`, `float`, `bool`, `string`;
- nullable types;
- enum;
- простые unions;
- атрибуты границ или named generator presets.

Ручной provider должен оставаться основным способом задания точного домена, но
автовывод снизит boilerplate для простых properties.

### 6. Regression corpus вместо одного seed

Полезно хранить несколько failing seeds или сериализуемые минимальные примеры,
повторять их перед random phase и удалять только после подтверждённого pass.
Для несериализуемых значений seed остаётся fallback. Нужна версия формата и
учёт версии генератора/пакета.

### 7. Более удобная модель providers

Текущая рекомендация делать generator/example methods публичными является
рабочим обходом Rector, но не хорошей ergonomics. Возможные направления:

- provider object;
- отдельный provider class;
- специальный атрибут/интеграция с Rector;
- поддержка static callable вне test class;
- диагностика лишних ключей в generator map, а не только отсутствующих.

### 8. Возможности более низкого приоритета

- targeted PBT с поиском максимума/минимума метрики;
- генерация pure functions для higher-order API;
- richer date/time, decimal и domain-specific generators;
- configurable shrinking strategy;
- sharding и явная модель совместимости с параллельным runner;
- адаптеры для PHPUnit/Pest, если расширение аудитории станет целью проекта.

## Рекомендуемый порядок работ

### Ближайший patch release

1. Запретить `Gen::filter()` возвращать значение, не прошедшее predicate.
2. Сделать ноль успешных runs ошибкой.
3. Добавить regression tests для обоих случаев.
4. Уточнить README и `llms.txt` по exhaustion/discard semantics.

### Следующий minor release

1. Добавить структурированный counterexample renderer и PHP/JSON export.
2. Унифицировать контракт `minSize`/`minLength`.
3. Добавить execution deadline.
4. Валидировать лишние generator keys.
5. Устранить ложную поддержку `TARGET_FUNCTION` или реализовать её.

### Следующий major release

1. Ввести generic-аннотации по всей публичной поверхности.
2. Унифицировать exhaustion API и исключения.
3. Перейти от single-seed replay к versioned regression corpus.
4. Пересмотреть provider ergonomics без обязательных public methods.

## Итоговая оценка

Пакет концептуально сильный и уже пригоден для реального использования,
особенно для библиотек с алгебраическими инвариантами, codecs, stateful
components и сложными границами входных данных.

До исправления критических пунктов потребителю следует избегать `Gen::filter()`
для predicates, которые могут оказаться недостижимыми, и всегда контролировать,
что property выполнил ненулевое число успешных проверок. Главный следующий шаг
проекта — укрепить корректность exhaustion/discards и сделать найденные
counterexamples непосредственно пригодными для отладки и закрепления в
регрессионных примерах.
