# rasuvaeff/property-testing

[![Latest Stable Version](https://poser.pugx.org/rasuvaeff/property-testing/v)](https://packagist.org/packages/rasuvaeff/property-testing)
[![Total Downloads](https://poser.pugx.org/rasuvaeff/property-testing/downloads)](https://packagist.org/packages/rasuvaeff/property-testing)
[![Build](https://github.com/rasuvaeff/property-testing/actions/workflows/build.yml/badge.svg)](https://github.com/rasuvaeff/property-testing/actions/workflows/build.yml)
[![Static analysis](https://github.com/rasuvaeff/property-testing/actions/workflows/static-analysis.yml/badge.svg)](https://github.com/rasuvaeff/property-testing/actions/workflows/static-analysis.yml)
[![Psalm level](https://img.shields.io/badge/psalm-level_1-blue.svg)](https://github.com/rasuvaeff/property-testing/actions/workflows/static-analysis.yml)
[![PHP](https://img.shields.io/packagist/dependency-v/rasuvaeff/property-testing/php)](https://packagist.org/packages/rasuvaeff/property-testing)
[![License](https://img.shields.io/badge/license-BSD--3--Clause-blue.svg)](LICENSE.md)
[English version](README.md)

Property-based тестирование для PHP 8.3+, реализованное как плагин для
тест-фреймворка [Testo](https://github.com/php-testo/testo). Генерируйте сотни
случайных входов на каждый тест, находите падающий и shrink'айте его до
минимального читаемого контрпримера.

> Используете AI-ассистента? [llms.txt](llms.txt) содержит компактный
> API-справочник, которым можно поделиться с моделью.

Начиная с 2.0 shrinking **интегрированный**: `generate()` возвращает
[`Shrinkable`](src/Shrinkable.php) — значение плюс ленивое дерево меньших
кандидатов — поэтому трансформированные генераторы (`Gen::map()`, `Gen::flatMap()`)
shrink'аются через свой исходный домен. Обновляетесь с 1.x? См. [UPGRADE.md](UPGRADE.md).

## Требования

- PHP 8.3+
- `ext-mbstring`
- `ext-random`
- [`testo/testo`](https://packagist.org/packages/testo/testo) `^0.10.25 || ^1.0`

## Установка

```bash
composer require --dev rasuvaeff/property-testing
```

Регистрация плагина не требуется: атрибут `#[Property]` саморегистрируется в
Testo через механизм обнаружения интерцепторов фреймворка.

## Использование

Пометьте тестовый метод атрибутом `#[Property]` и укажите метод-генератор,
сопоставляющий имя каждого параметра с фабрикой `Gen`. Раннер генерирует
случайные аргументы, прогоняет свойство `runs` раз, а при первом падении
shrink'ает контрпример до минимального.

```php
use Rasuvaeff\PropertyTesting\Assume;
use Rasuvaeff\PropertyTesting\Gen;
use Rasuvaeff\PropertyTesting\Property;
use Testo\Assert;
use Testo\Test;

#[Test]
final class RetryPolicyPropertyTest
{
    #[Property(runs: 500, generators: 'delayGenerators')]
    public function delayNeverExceedsCap(int $maxAttempts, int $baseSeconds, int $cap, int $attempts): void
    {
        Assume::that($cap >= $baseSeconds);

        $policy = WebhookRetryPolicy::exponential($maxAttempts, $baseSeconds, $cap);

        Assert::true($policy->nextDelaySeconds($attempts) <= $cap);
    }

    /** @return array<string, \Rasuvaeff\PropertyTesting\ArbitraryInterface> */
    public static function delayGenerators(): array
    {
        return [
            'maxAttempts' => Gen::intBetween(1, 50),
            'baseSeconds' => Gen::intBetween(1, 300),
            'cap' => Gen::intBetween(1, 86400),
            'attempts' => Gen::intBetween(1, 100),
        ];
    }
}
```

При падении контрпример попадает в вывод теста:

```
Property falsified after 246 successful run(s); seed=7382910
  Original: maxAttempts=17, baseSeconds=91, cap=847, attempts=23
  Shrunk:   maxAttempts=1, baseSeconds=848, cap=847, attempts=1 (12 shrink step(s), 41 trial(s))
  Changed:  maxAttempts=17 -> 1, baseSeconds=91 -> 848, attempts=23 -> 1
```

Строка `Changed:` показывает diff исходного и shrunk-контрпримера: аргументы,
которые shrinker не тронул (здесь `cap`), опущены, поэтому входы, реально
приводящие к падению, видны сразу. `trial(s)` считает каждого кандидата,
которого shrinker прогнал (принятого и отвергнутого); `shrink step(s)` — только
принятые.

Чтобы воспроизвести точный прогон, передайте сообщённый seed обратно в атрибут:

```php
#[Property(runs: 500, seed: 7382910, generators: 'delayGenerators')]
```

### Почему генераторы вынесены в отдельный метод

Аргументы PHP-атрибутов должны быть константными выражениями, поэтому
`#[Given('x', Gen::int())]` записать нельзя. Вместо этого назовите метод,
возвращающий `array<string, ArbitraryInterface>` с ключами по именам параметров.
Если аргумент `generators` опущен, раннер ищет метод с именем `<testMethod>Generators`.

Методы генераторов (и примеров) объявляйте `public static` — либо `public`,
если телу нужен `$this`. Их единственный вызов — рефлексия этого пакета, поэтому
статический анализатор видит их как неиспользуемые: Rector из набора dead-code
удаляет private-варианты (`RemoveUnusedPrivateMethodRector`). Public-методы
безопасны, а Testo никогда не считает метод с не-void-возвратом тестом.

### Генераторы

| Фабрика | Производит | Shrink |
|---|---|---|
| `Gen::int()` | `IntArbitrary`, `PHP_INT_MIN..PHP_INT_MAX` | к `0` |
| `Gen::intBetween($min, $max)` | `IntArbitrary`, `[$min, $max]` | к `0`, ограничен диапазоном |
| `Gen::intPositive()` | `IntArbitrary`, `1..PHP_INT_MAX` | к `1` |
| `Gen::float()` | `FloatArbitrary`, `[0.0, 1.0)` | к `0.0` |
| `Gen::floatBetween($min, $max)` | `FloatArbitrary`, `[$min, $max]` | к `0.0`, ограничен диапазоном |
| `Gen::bool()` | `BoolArbitrary`, `true` / `false` | `true` -> `false` |
| `Gen::string()` | `StringArbitrary`, Unicode, длина 0..100 | к `''`, затем по длине, затем каждый символ к `a` |
| `Gen::stringAscii()` | `StringArbitrary`, печатный ASCII, длина 0..100 | к `''`, затем по длине, затем каждый символ к `a` |
| `Gen::stringOf($min, $max)` | `StringArbitrary`, Unicode, ограниченная длина | к `''`, затем по длине, затем каждый символ к `a` |
| `Gen::stringFrom($alphabet, $min, $max)` | `CharsetStringArbitrary`, символы из фиксированного алфавита (многобайтовые допустимы) | к `''`, затем по длине, затем каждый символ к первому символу алфавита |
| `Gen::bytes($min, $max)` | `BytesArbitrary`, сырые байтовые строки (байты 0..255) | к `''`, затем по длине, затем каждый байт к `"\x00"` |
| `Gen::arrayOf($element, $min, $max)` | `ArrayArbitrary`, списки из `$element`, размер по умолчанию 0..100 | к `[]`, затем по длине, затем каждый элемент |
| `Gen::nonEmptyArrayOf($element, $max)` | `ArrayArbitrary`, непустые списки | по длине (никогда ниже 1), затем каждый элемент |
| `Gen::uniqueArrayOf($element, $min, $max)` | `UniqueArrayArbitrary`, списки попарно различных элементов | как `arrayOf`, но конфликтующие с другими элементы пропускаются |
| `Gen::dictOf($key, $value, $min, $max)` | `DictionaryArbitrary`, map'ы с различными ключами из `$key` (int/string) и значениями из `$value`, размер по умолчанию 0..100 | к `[]`, затем по размеру, затем каждое значение (ключи фиксированы) |
| `Gen::record($shape)` | `RecordArbitrary`, map фиксированной формы `['field' => $arb, ...]` | каждое поле через свой arbitrary, набор ключей фиксирован |
| `Gen::elements($array)` | `OneOfArbitrary`, одно значение из массива (массивная форма `oneOf`) | к более ранним различным значениям |
| `Gen::enum(SomeEnum::class)` | `OneOfArbitrary` по case'ам enum'а | к более ранним case'ам (объявляйте простые case'ы первыми) |
| `Gen::constant($value)` | `ConstantArbitrary`, всегда `$value` | не shrink'ается |
| `Gen::char()` | `StringArbitrary`, один печатный ASCII-символ | к `a` |
| `Gen::uuid()` | `UuidArbitrary`, строки UUID RFC 4122 v4 | не shrink'ается |
| `Gen::datetime($min, $max)` | `DateTimeArbitrary`, UTC `DateTimeImmutable`, timestamp в `[$min, $max]` | к Unix-эпохе, ограничен |
| `Gen::floatSpecial()` | `OneOfArbitrary` по `NAN`, `±INF`, `-0.0` и краям представления float | к более ранним specials |
| `Gen::intRange($min, $max)` | `FlatMappedArbitrary`, упорядоченные пары `[lo, hi]` с `lo <= hi` | обе границы shrink'аются, порядок всегда соблюдается |
| `Gen::recursive($leaf, $wrap, $maxDepth)` | ограниченные рекурсивные структуры: `$wrap` поднимает arbitrary предыдущего уровня | внутри ветви, сгенерировавшей значение |
| `Gen::oneOf(...$values)` | `OneOfArbitrary`, одно из переданных значений | к более ранним различным значениям (кладите простые значения первыми) |
| `Gen::nullable($inner)` | `NullableArbitrary`, `null` или значение `$inner` | предпочитает `null`, затем внутреннее дерево |
| `Gen::map($inner, $fn)` | `MappedArbitrary`, `$inner`, трансформированный `$fn` | через внутреннее дерево, с повторным применением `$fn` |
| `Gen::flatMap($inner, $fn)` | `FlatMappedArbitrary`, зависимый генератор, возвращаемый `$fn($innerValue)` | сначала исходное значение (зависимое регенерируется), затем зависимое дерево |
| `Gen::filter($inner, $predicate)` | `FilteredArbitrary`, значения `$inner`, удовлетворяющие `$predicate` (бросает `GenerationExhausted` после 100 отклонений — никогда не отдаёт значение вне домена) | внутреннее дерево, обрезает кандидатов, не прошедших предикат |
| `Gen::tuple(...$elements)` | `TupleArbitrary`, кортеж фиксированной арности, по значению на элемент | каждая позиция через свой элемент, арность фиксирована |
| `Gen::frequency($pairs)` | `FrequencyArbitrary`, взвешенный выбор по парам `[weight, arbitrary]` | внутри ветви, сгенерировавшей значение |
| `Gen::ipv4()` | IPv4 dotted-quad строки | каждый октет к `0` |
| `Gen::email()` | адреса вида `local@label.tld` | к кратчайшим local/label и первому TLD |
| `Gen::url()` | URL'ы `http(s)://host.tld[/path]` | к `http://a.com` |
| `Gen::json($maxDepth)` | JSON-кодируемое значение (null/bool/int/float/string/list/object) | внутри сгенерированной структуры |
| `Gen::jsonString($maxDepth)` | текст `json_encode` от `Gen::json()` | через дерево значения |
| `Gen::regex($pattern)` / `Gen::stringMatching($pattern)` | строки, матчащие подмножество regex (компилируется в комбинаторы) | более короткие/простые матчи (через скомпилированные деревья) |

Числовые генераторы (`int*`, `float*`) **смещены в сторону границ**: примерно
каждый пятый draw возвращает in-range edge-значение (`0`, `±1`, `min`, `max`
для int; `0.0` или `min` для float), где и концентрируются баги, вместо
равномерного распределения. На shrinking это не влияет.

Размерные генераторы гарантируют свой **минимум**: `uniqueArrayOf`/`dictOf`
(различные элементы/ключи) и `commands` (применимые шаги) могут не дотянуть до
*запрошенного* размера, когда пространство значений исчерпано, но никогда не
опускаются ниже `$min` — недостижимый минимум бросает `GenerationExhausted`
вместо того, чтобы отдать свойству слишком маленькое значение.

### Зависимые генераторы (`flatMap`)

Когда домен одного входа зависит от другого — список плюс валидный индекс в
нём, размер плюс payload этого размера — `Gen::flatMap()` передаёт каждое
сгенерированное значение в замыкание, возвращающее arbitrary для финального
значения. В отличие от `Assume::that()`-гарда, прогоны не отбрасываются, а
shrink'аются оба уровня: сначала исходное значение (зависимое регенерируется
детерминированно из seed прогона), затем зависимое при фиксированном исходном.

```php
/** @return array<string, ArbitraryInterface> */
public static function sliceGenerators(): array
{
    return ['pair' => Gen::flatMap(
        Gen::nonEmptyArrayOf(Gen::int()),
        static fn(array $items): ArbitraryInterface => Gen::tuple(
            Gen::constant($items),
            Gen::intBetween(0, count($items) - 1), // always a valid index
        ),
    )];
}
```

### Draw внутри тела (`Gen::draw`)

Если несколько зависимых значений делают вложенный `flatMap` громоздким,
draw'те их внутри тела свойства через `Gen::draw()`. Домен может зависеть от
чего угодно уже в скоупе — параметров, предыдущих draw'ов, промежуточных
результатов:

```php
#[Property(runs: 200)]
public function sliceIsContainedInTheList(array $xs): void
{
    $from = Gen::draw(Gen::intBetween(0, count($xs)));
    $to = Gen::draw(Gen::intBetween($from, count($xs))); // depends on $from

    foreach (array_slice($xs, $from, $to - $from) as $item) {
        Assert::true(in_array($item, $xs, true));
    }
}
```

Draw-значения shrink'аются вместе с параметрами. Раннер записывает каждый draw
на ленту повторов; при падении свойства shrink'ает каждый записанный draw через
его собственное дерево и перезапускает тело с лентой, проигранной по позиции.
Shrink'нутый параметр может изменить control flow тела: draw'ы за концом ленты
генерируются заново, а draw'ы, до которых укороченный прогон больше не доходит,
отбрасываются. Контрпримеры сообщают draw'и как `draw#1`, `draw#2`, ... рядом
с именованными параметрами (а `PROPERTY_VERBOSE` логирует их на каждый прогон).

Два важных момента:

- Проигранный draw отдаётся по позиции и **не** перевалидируется против
  (возможно, более узкого) arbitrary'я нового control flow — та же модель, что
  у `gen()` в fast-check. Утверждайте то, что телу реально нужно, а не
  полагайтесь на диапазон draw'а после shrinking'а.
- Поскольку лента может отрасти заново во время shrinking'а, аргумент
  конечности дерева сам по себе больше не работает; при наличии draw'ов
  принимаемые shrink-шаги ограничены (1000 по умолчанию, `maxShrinks` при
  установке всё равно побеждает).

`Gen::draw()` валиден только пока раннер выполняет тело свойства; в любом
другом месте бросает исключение. Для одиночного зависимого значения предпочитайте
`flatMap` — он держит весь домен видимым в методе-генераторе.

### `Assume::that()`

Отбрасывает текущую попытку, если предусловие не выполнено. `runs` — это число
успешных проверок, поэтому отброшенные попытки замещаются новыми. Предпочитайте
`Assume::that()` вместо `Gen::filter()`, когда доля отбрасываний низка; если
отбрасывается больше 90% попыток, раннер предупреждает о невероятно
сконфигурированных генераторах.

```php
Assume::that($cap >= $baseSeconds);
```

Число отброшенных попыток ограничено `maxDiscards` (по умолчанию `runs * 10`).
При превышении бюджета свойство падает с `GaveUpException`, публичные поля
которого сообщают required/successful runs, discarded attempts, total attempts
и бюджет. Переопределите его, когда легитимный домен разрежен:

```php
#[Property(runs: 200, maxDiscards: 5_000)]
```

Конструируйте валидные входы (`Gen::flatMap()` / `Gen::draw()`) вместо
увеличения бюджета, когда зависимость можно закодировать напрямую.

### Ограничение shrink-работы

По умолчанию shrinking идёт, пока хоть один меньший кандидат всё ещё падает, с
перезапуском свойства на каждый принимаемый шаг. Для дорогих свойств или очень
больших входов можно ограничить число принимаемых shrink-шагов через `maxShrinks`:

```php
#[Property(runs: 200, maxShrinks: 25)]
```

`maxShrinks: null` (по умолчанию) означает без лимита. `maxShrinks: 0` полностью
отключает shrinking и сообщает исходный контрпример без изменений. Лимит считает
*принятые* shrink-шаги, а не выполнения тестов.

### Дедлайны и временные бюджеты

Патологические входы (катастрофический regex, глубокая рекурсия, неограниченный
backoff) проявляются как время, а не как упавшая assertion. Две опциональные
wall-clock-блокировки превращают их в явные падения:

```php
#[Property(runs: 200, timeoutMs: 100, budgetMs: 5_000)]
```

- `timeoutMs` — дедлайн **одного прогона** (случайного или explicit example).
  Тело, превысившее лимит, падает со свойством через `DeadlineExceededException`
  с указанием виновного входа и измеренного времени. Вход сообщается as-is, без
  shrink (принятие shrink-кандидата потребовало бы повторного замера wall time,
  а таймерный шум делает такой спуск недетерминированным). Время измеряется
  после возврата тела — зависшее тело в синхронном PHP прервать нельзя.
- `budgetMs` — бюджет **всей случайной фазы**. Если он исчерпан до завершения
  `runs` успешных проверок, свойство падает с `TimeBudgetExceededException`,
  раскрывающим счётчики completed/required, — медленное свойство не может
  молча проверить меньше заявленного.

Оба по умолчанию `null` (выключены). Упавшая assertion в медленном прогоне
приоритетнее дедлайна — фальсифицированный контрпример важнее.

### Написание собственного arbitrary

`Gen` покрывает типовые случаи, но любое пространство значений достижимо прямой
реализацией [`ArbitraryInterface`](src/ArbitraryInterface.php): `generate(Random)`
возвращает [`Shrinkable`](src/Shrinkable.php) — вытянутое значение плюс ленивое
дерево меньших кандидатов, наиболее агрессивных первыми, каждый со своим
поддеревом. Случайность тяните только через внедрённый `Random` (`int()`,
`float()`, `bytes()`), чтобы прогоны с seed оставались воспроизводимыми.

```php
use Rasuvaeff\PropertyTesting\ArbitraryInterface;
use Rasuvaeff\PropertyTesting\Random;
use Rasuvaeff\PropertyTesting\Shrinkable;

/**
 * Even integers in [0, $max], shrinking toward 0 in even steps.
 */
final readonly class EvenArbitrary implements ArbitraryInterface
{
    public function __construct(private int $max = 1000) {}

    #[\Override]
    public function generate(Random $random): Shrinkable
    {
        return $this->tree($random->int(0, intdiv($this->max, 2)) * 2);
    }

    private function tree(int $value): Shrinkable
    {
        return Shrinkable::of($value, function () use ($value): \Generator {
            if ($value === 0) {
                return;
            }

            yield $this->tree(0);

            $half = intdiv($value, 4) * 2; // stay even

            if ($half !== 0 && $half !== $value) {
                yield $this->tree($half);
            }
        });
    }
}
```

Собственный arbitrary используется как любой встроенный: верните его из
метода-генератора с ключом по имени параметра. `Shrinkable::leaf($value)`
строит терминальный узел (без кандидатов); `Shrinkable::of($value, $closure)`
навешивает лениво вычисляемых кандидатов; `Shrinkable::map($fn)` трансформирует
всё дерево. Держите каждую ветвь дерева конечной и никогда не yield'айте
кандидата, равного родителю, — именно это гарантирует завершение shrinking'а.

### Переменные окружения

Две переменные окружения настраивают прогоны без правки атрибутов — удобно в
CI:

| Переменная | Эффект |
|---|---|
| `PROPERTY_RUNS` | Положительное целое, переопределяющее счётчик прогонов каждого свойства (поднимайте в CI). |
| `PROPERTY_SEED` | Целочисленный seed для любого свойства, в атрибуте которого нет `seed` (повтор всего набора). Явный `seed` в атрибуте всё равно побеждает. |
| `PROPERTY_VERBOSE` | Любое значение, кроме `''`/`0`, логирует сгенерированные аргументы каждого прогона и при падении — каждый принятый shrink-шаг (`shrink step 3: x=63 -> 51`): видно и что проигранный seed скармливает свойству, и как shrinker спускается. |
| `PROPERTY_DB` | Путь к каталогу, включающий replay регрессий (ниже). Не задано — фича выключена, ничего не пишется. |

### Повтор последнего падения

Задайте `PROPERTY_DB` как каталог, и фальсифицированное свойство записывает
упавший seed. При следующем запуске этот seed гоняется **первым** (если только
атрибут не фиксирует свой `seed`): всё ещё падающий seed сообщается немедленно
для быстрой обратной связи, а seed, который больше не падает, забывается.
Хранится только seed — никогда не сгенерированные значения (они могут быть
объектами или замыканиями) — поэтому повтор seed воспроизводит тот же draw.
Хранилище — один небольшой файл на свойство (`<sha1(id)>.seed`); добавьте
каталог в `.gitignore`.

### Явные примеры

Фиксированные входы закрепляют найденный баг как постоянный кейс, который
выполняется при каждом запуске наряду со случайными. Объявите метод
`<testMethod>Examples` (или укажите его через `#[Property(examples: 'method')]`),
возвращающий позиционные кортежи аргументов; каждый выполняется **до** случайных
входов и сообщается дословно (без shrink — это уже минимальный кейс, который вы
закрепили) через `ExampleViolationException`.

```php
#[Test]
#[Property(generators: 'ints')]
public function additionCommutes(int $a, int $b): void
{
    Assert::same($a + $b, $b + $a);
}

/** @return list<array{int, int}> */
public static function additionCommutesExamples(): array
{
    return [[0, 0], [PHP_INT_MAX, 1]]; // regressions that must always run
}
```

### Проверка распределения

Свойство может пройти вакуумно, если его генераторы никогда не достигают
интересных входов. `Classify` записывает метки на каждый прогон; после
полностью прошедшего свойства раннер печатает долю прогонов, попавших в каждую
метку.

```php
#[Property(runs: 500)]
public function holds(int $n): void
{
    Classify::when($n === 0, 'zero');
    Classify::label($n % 2 === 0 ? 'even' : 'odd');
    // ... assertions ...
}
// Property "holds" distribution: odd 51% (255/500), even 49% (245/500), zero 1% (3/500)
```

Метка, записанная несколько раз в одном прогоне, считается для этого прогона
один раз.

### Инфорсмент распределения

`Classify::cover()` превращает печатную подсказку в жёсткое требование: метка
должна встречаться минимум в заданном проценте успешных прогонов, иначе
свойство **падает** с `CoverageViolationException` — даже если каждый прогон
прошёл. Используйте это, чтобы сделать вакуумные проходы невозможными в CI.

```php
#[Property(runs: 500)]
public function holds(int $n): void
{
    Classify::cover($n % 2 === 0, 'even', 30.0); // fail if < 30% of runs are even
    // ... assertions ...
}
```

Отброшенные попытки (`Assume::that()`) исключаются из знаменателя и замещаются,
пока не выполнятся все запрошенные успешные прогоны. Превышение `maxDiscards`
роняет свойство через `GaveUpException` (см. `Assume::that()`).

### Сэмплирование генератора

`Gen::sample()` жадно генерирует значения из любого arbitrary для фиксированного
seed — быстрый способ глазами оценить, что производит генератор (возвращает
значения, а не arbitrary).

```php
Gen::sample(Gen::intBetween(1, 6), count: 5, seed: 42); // [3, 1, 6, 6, 2]
```

`Gen::sampleShrinks()` делает то же для shrink-дерева: генерирует одно значение
и перечисляет его первых прямых shrink-кандидатов — самый быстрый способ
убедиться, что собственный arbitrary shrink'ается так, как вы задумали.

```php
Gen::sampleShrinks(Gen::intBetween(0, 100), seed: 1);
// ['value' => 87, 'shrinks' => [0, 44, 66, 77, 82, 85, 86]]
```

### Экспорт контрпримера

`CounterExample::toArray()` и `toJson()` отдают нормализованное представление
для репортёров и CI-артефактов, включая вложенное состояние DTO и маркеры
рекурсии. Чтобы закрепить shrunk scalar/array/enum-кейс как регрессионный
пример:

```php
$code = $violation->getCounterExample()->toExamplesCode('holdsExamples');
```

Сгенерированный метод yield'ит аргументы в порядке параметров. Неподдерживаемые
runtime-объекты бросают `LogicException` вместо генерации кода, который не
смог бы выполниться.

### Рецепты

Зависимые значения без отбрасываний — конструируйте, а не фильтруйте:

```php
// A size and a payload of exactly that size.
Gen::flatMap(Gen::intBetween(1, 32), static fn(int $size): ArbitraryInterface
    => Gen::tuple(Gen::constant($size), Gen::bytes($size, $size)));

// An ordered interval: Gen::intRange(0, 1440) yields [lo, hi] with lo <= hi.

// Domain strings from an alphabet instead of filtering Unicode.
Gen::stringFrom('abcdefghijklmnopqrstuvwxyz0123456789-', 1, 63); // hostname label
```

Ограниченные рекурсивные данные:

```php
use Rasuvaeff\PropertyTesting\Arbitrary\ArrayArbitrary;

// JSON-ish scalars nested in small arrays, at most 3 levels deep.
Gen::recursive(
    Gen::oneOf(null, true, false, 0, 1, 'a'),
    static fn(ArbitraryInterface $inner): ArbitraryInterface => new ArrayArbitrary($inner, 0, 3),
    maxDepth: 3,
);
```

Держите ветвление ветвей небольшим (ограниченные размеры массивов): ширина
умножается на каждом уровне вложенности.

### Stateful / model-based тестирование

Некоторые баги проступают только в *последовательности* операций — счётчик,
переполняющийся после N инкрементов, кэш, возвращающий устаревшие данные, стек,
теряющий порядок. Model-based-тестирование генерирует случайные
последовательности команд, прогоняет каждую против реальной системы, зеркалируя
её в упрощённой модели, а при падении **shrink'ает последовательность** до
кратчайшей, всё ещё ломающей поведение.

Реализуйте [`Command`](src/StateMachine/Command.php) — четыре почти чистых
ответственности плюс метку:

| Метод | Назначение |
|---|---|
| `preCondition(mixed $model): bool` | Может ли эта команда выполниться в текущем состоянии модели? Гейтит генерацию и при replay — выполняется команда или пропускается. |
| `nextState(mixed $model): mixed` | Ожидаемое следующее состояние модели (чисто; возвращает новую модель, ничего не мутирует). |
| `run(mixed $model, mixed $system): mixed` | Выполнить против системы под тестом; вернуть наблюдаемый результат. |
| `postCondition(mixed $model, mixed $result): bool` | Проверить результат против модели предсостояния. Вернуть `false` (или бросить), чтобы фальсифицировать. |
| `__toString(): string` | Метка для трассировки контрпримера. |

`Gen::commands($initialModel, $commandGenerators)` строит валидные
последовательности (каждый шаг добавляет команду с выполненным предусловием,
затем двигает модель), а `StateMachine::check()` гоняет сгенерированную
последовательность против свежей системы:

```php
use Rasuvaeff\PropertyTesting\Gen;
use Rasuvaeff\PropertyTesting\Property;
use Rasuvaeff\PropertyTesting\StateMachine\CommandSequence;
use Rasuvaeff\PropertyTesting\StateMachine\StateMachine;
use Testo\Test;

#[Test]
final class StackModelTest
{
    #[Property(runs: 200)]
    public function stackBehavesLikeItsModel(CommandSequence $sequence): void
    {
        StateMachine::check($sequence, static fn(): Stack => new Stack());
    }

    /** @return array<string, \Rasuvaeff\PropertyTesting\ArbitraryInterface> */
    public static function stackBehavesLikeItsModelGenerators(): array
    {
        return ['sequence' => Gen::commands([], [
            Gen::map(Gen::intBetween(0, 99), static fn(int $v): Command => new Push($v)),
            Gen::constant(new Pop()),
        ])];
    }
}
```

Shrinking удаляет целые блоки команд (вплоть до одной, чтобы падающий шаг в
середине изолировать), а затем упрощает параметры каждой команды через её
собственное дерево. Поскольку раннер перепроверяет каждое предусловие и
пропускает любой шаг, инвалидированный удалённым/упрощённым шагом, каждая
shrunk-последовательность остаётся корректной. Контрпример рендерится как
читаемая трасса, а нарушенное постусловие бросает
[`PostconditionViolation`](src/StateMachine/PostconditionViolation.php) с
указанием шага:

```
Property falsified after 7 successful run(s); seed=42
  Original: sequence=[Push(3), Pop(), Push(5), Push(1), Pop(), Pop()]
  Shrunk:   sequence=[Push(0), Push(1), Pop()] (9 shrink step(s))
  Failure:  Postcondition failed at step 3 for command Pop(); sequence: [Push(0), Push(1), Pop()]
```

Полный пример стека — см. [`examples/state_machine.php`](examples/state_machine.php).

## Безопасность

Этот пакет выполняет тестовые методы через рефлексию (чтение атрибута `#[Property]`
и вызов метода-генератора) и через пайплайн Testo. Fallback-интерцептор Testo —
`PropertyInterceptor`. Сам он не выполняет никаких I/O, SQL, shell или
сетевых операций. Случайные значения генерируются движком MT19937 в PHP с
засевом от сообщённого seed; не полагайтесь на них в криптографических целях.

## Примеры

См. [examples/](examples/) — исполняемые скрипты.

| Скрипт | Что показывает | Нужен сервер? |
|---|---|---|
| `basic.php` | свойство, которое выполняется; фальсифицированное свойство; shrinking на деревьях | нет |
| `property_test.php` | каноническое использование `#[Property]` как реального Testo-кейса | нет |
| `generators.php` | `sample`, смещение к границам, `uuid`, `datetime`, `dictOf`, `record`, `flatMap` | нет |
| `state_machine.php` | stateful / model-based-тестирование: `Command`, `Gen::commands()`, `StateMachine::check()` | нет |

## Разработка

На хосте нет PHP/Composer. Команды гоняются в Docker через образ `composer:2`:

```bash
docker run --rm -v "$PWD":/app -w /app composer:2 composer install
docker run --rm -v "$PWD":/app -w /app composer:2 composer build
docker run --rm -v "$PWD":/app -w /app composer:2 composer cs:fix
docker run --rm -v "$PWD":/app -w /app composer:2 composer test
docker run --rm -v "$PWD":/app -w /app composer:2 composer release-check
```

Или через Make:

```bash
make install
make build
make cs-fix
make test
make test-coverage
make mutation
make release-check
```

`make test-coverage` и `make mutation` поднимают `pcov` внутри контейнера
`composer:2`, потому что в базовом образе нет драйвера покрытия.

## Лицензия

[BSD-3-Clause](LICENSE.md)
