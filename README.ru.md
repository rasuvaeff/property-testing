# rasuvaeff/property-testing
[![Latest Stable Version](https://poser.pugx.org/rasuvaeff/property-testing/v)](https://packagist.org/packages/rasuvaeff/property-testing)
[![Total Downloads](https://poser.pugx.org/rasuvaeff/property-testing/downloads)](https://packagist.org/packages/rasuvaeff/property-testing)
[![Build](https://github.com/rasuvaeff/property-testing/actions/workflows/build.yml/badge.svg)](https://github.com/rasuvaeff/property-testing/actions/workflows/build.yml)
[![Static analysis](https://github.com/rasuvaeff/property-testing/actions/workflows/static-analysis.yml/badge.svg)](https://github.com/rasuvaeff/property-testing/actions/workflows/static-analysis.yml)
[![Psalm level](https://img.shields.io/badge/psalm-level_1-blue.svg)](https://github.com/rasuvaeff/property-testing/actions/workflows/static-analysis.yml)
[![PHP](https://img.shields.io/packagist/dependency-v/rasuvaeff/property-testing/php)](https://packagist.org/packages/rasuvaeff/property-testing)
[![License](https://img.shields.io/badge/license-BSD--3--Clause-blue.svg)](LICENSE.md)
[English version](README.md)

 Тестирование на основе свойств для PHP 8.3+, построенное как плагин для
[Testo](https://github.com/php-testo/testo) testing framework. Generate hundreds
случайных входных данных для каждого теста, найдите неудачный и сократите его до минимального
 контрпримера, который вы действительно можете прочитать.

 > Используете помощника по программированию с искусственным интеллектом? [llms.txt](llms.txt) содержит компактную ссылку на API, которой вы можете поделиться с моделью.

 Поскольку в версии 2.0 сжатие **интегрировано**: `generate()` возвращает
 [`Shrinkable`](src/Shrinkable.php) — значение плюс ленивое дерево меньших
 кандидатов — поэтому преобразованные генераторы (`Gen::map()`, `Gen::flatMap()`) сжимают
 через свой исходный домен. Обновление с версии 1.x? См. [UPGRADE.md](UPGRADE.md). @@ЛИНИЯ@@
## Требования
- PHP 8.3+
 - `ext-mbstring`
 - `ext-random`
- [`testo/testo`](https://packagist.org/packages/testo/testo) `^0.10.25 || ^1.0`
## Установка
```bash
composer require --dev rasuvaeff/property-testing
```
Регистрация плагина не требуется: атрибут `#[Property]` самостоятельно регистрируется
 в Testo посредством обнаружения перехватчика платформы. @@ЛИНИЯ@@
## Использование
Отметьте тестовый метод знаком `#[Property]` и укажите его на метод генератора, который
 сопоставляет каждое имя параметра с фабрикой `Gen`.
 Бегун генерирует случайные аргументы, запускает свойство несколько раз, и при
 первая ошибка сжимает контрпример до минимального. @@ЛИНИЯ@@
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
В случае неудачи контрпример отображается в выходных данных теста:

```
Property falsified after 246 successful run(s); seed=7382910
  Original: maxAttempts=17, baseSeconds=91, cap=847, attempts=23
  Shrunk:   maxAttempts=1, baseSeconds=848, cap=847, attempts=1 (12 shrink step(s))
```
Воспроизведите точный запуск, передав полученное начальное значение обратно в атрибут:
.
```php
#[Property(runs: 500, seed: 7382910, generators: 'delayGenerators')]
```
### Почему генераторы вынесены в отдельный метод
Аргументы атрибутов PHP должны быть константными выражениями, поэтому `#[Given('x', Gen::int())]`
 не является выраженным. Вместо этого назовите метод, который возвращает
 `array<string, ArbitraryInterface>` с ключом по имени параметра. Когда аргумент `generators`
 опущен, бегун возвращается к методу с именем `<testMethod>Generators`.

 Объявите методы генераторов (и примеров) `public static` — или `public`, если телу
 требуется `$this`. Их единственным местом вызова является отражение этого пакета, поэтому статический анализ
 видит их как неиспользуемые: набор мертвого кода Rector удаляет частные
 (`RemoveUnusedPrivateMethodRector`). Публичные методы безопасны, и Testo
 никогда не рассматривает метод, не возвращающий void, как тест. @@ЛИНИЯ@@
### Генераторы
| Фабрика | Производит | Сжимается |
 |---|---|---|
 | `Gen::int()` | `IntArbitrary`, `PHP_INT_MIN..PHP_INT_MAX` | к `0` |
 | `Gen::intBetween($min, $max)` | `IntArbitrary`, `[$min, $max]` | в сторону `0`, ограничено диапазоном |
 | `Gen::intPositive()` | `IntArbitrary`, `1..PHP_INT_MAX` | в сторону `1` |
 | `Gen::float()` | `FloatArbitrary`, `[0.0, 1.0)` | в сторону `0,0` |
 | `Gen::floatBetween($min, $max)` | `FloatArbitrary`, `[$min, $max]` | в сторону `0.0`, ограничено диапазоном |
 | `Gen::bool()` | `BoolArbitrary`, `истина`/`ложь` | `истина` -> `ложь` |
 | `Gen::string()` | `StringArbitrary`, Unicode, длина 0..100 | в сторону `''`, затем по длине, затем каждый символ в сторону `'' |
 | `Gen::stringAscii()` | `StringArbitrary`, печатный ASCII, длина 0..100 | в сторону `''`, затем по длине, затем каждый символ в сторону `'' |
 | `Gen::stringOf($min, $max)` | `StringArbitrary`, Unicode, ограниченная длина | в сторону `''`, затем по длине, затем каждый символ в сторону `'' |
 | `Gen::stringFrom($alphabet, $min, $max)` | `CharsetStringArbitrary`, символы фиксированного алфавита (многобайтовые ОК) | в сторону `''`, затем по длине, затем каждый символ в сторону первого символа алфавита |
 | `Gen::bytes($min, $max)` | `BytesArbitrary`, необработанные байтовые строки (байты 0..255) | в сторону `''`, затем по длине, затем каждый байт в сторону `"\x00"` |
 | `Gen::arrayOf($element, $min, $max)` | `ArrayArbitrary`, списки `$element`, размер 0..100 по умолчанию | в сторону `[]`, затем по длине, затем каждый элемент |
 | `Gen::nonEmptyArrayOf($element, $max)` | `ArrayArbitrary`, непустые списки | по длине (никогда не ниже 1), тогда каждый элемент |
 | `Gen::uniqueArrayOf($element, $min, $max)` | `UniqueArrayArbitrary`, списки попарно различных элементов | как `arrayOf`, но кандидаты элементов, конфликтующие с другим элементом, пропускаются |
 | `Gen::dictOf($key, $value, $min, $max)` | `DictionaryArbitrary`, карты с ключами из `$key` (int/string) и значениями из `$value`, размер 0..100 по умолчанию | в сторону `[]`, затем по размеру, затем по каждому значению (ключи фиксированные) |
 | `Gen::record($shape)` | `RecordArbitrary`, карта фиксированной формы `['field' => $arb, ...]` | каждое поле через произвольный фиксированный набор ключей |
 | `Gen::elements($array)` | `OneOfArbitrary`, одно значение из массива (форма массива `oneOf`) | к ранее перечисленным ценностям |
 | `Gen::enum(SomeEnum::class)` | `OneOfArbitrary` для случаев перечисления | в сторону ранее заявленных случаев (сначала объявляйте более простые случаи) |
 | `Gen::constant($value)` | `ConstantArbitrary`, всегда `$value` | не сжимается |
 | `Gen::char()` | `StringArbitrary`, один печатный символ ASCII | в сторону `а` |
 | `Gen::uuid()` | `UuidArbitrary`, строки UUID RFC 4122 v4 | не сжимается |
 | `Gen::datetime($min, $max)` | `DateTimeArbitrary`, UTC `DateTimeImmutable`, временная метка в `[$min, $max]` | навстречу эпохе Unix, зажат |
| `Gen::floatSpecial()` | OneOfArbitrary для NAN, ±INF, -0.0 и ребер представления с плавающей запятой | к ранее перечисленным специальным предложениям |
 | `Gen::intRange($min, $max)` | `FlatMappedArbitrary`, упорядоченные пары `[lo, hi]` с `lo <= hi` | обе границы сжимаются, порядок всегда сохраняется |
 | `Gen::recursive($leaf, $wrap, $maxDepth)` | ограниченные рекурсивные структуры: `$wrap` поднимает произвольное значение предыдущего уровня | внутри ветки, создавшей значение |
 | `Gen::oneOf(...$values)` | `OneOfArbitrary`, одно из заданных значений | к ранее перечисленным отдельным значениям (сначала указывайте более простые значения) |
 | `Gen::nullable($inner)` | `NullableArbitrary`, `null` или значение `$inner` | предпочитает `null`, тогда внутреннее дерево |
 | `Gen::map($inner, $fn)` | `MappedArbitrary`, `$inner` преобразовано `$fn` | через внутреннее дерево, повторно применяя `$fn` |
 | `Gen::flatMap($inner, $fn)` | `FlatMappedArbitrary`, зависимый генератор, возвращаемый `$fn($innerValue)` | сначала исходное значение (регенерируется зависимое значение), затем зависимое дерево |
 | `Gen::filter($inner, $predicate)` | `FilteredArbitrary`, значения `$inner`, удовлетворяющие `$predicate` | внутреннее дерево, обрезка кандидатов, не выполнивших предикат |
 | `Gen::tuple(...$elements)` | `TupleArbitrary`, кортеж с фиксированной арностью, одно значение на элемент | каждая позиция через свой элемент, арность фиксированная |
 | `Поколение::частота($pairs)` | `FrequencyArbitrary`, взвешенный выбор для пар `[вес, произвольный]` | внутри ветки, создавшей значение |
 | `Gen::ipv4()` | IPv4-четверенные строки | каждый октет в сторону `0` |
 | `Gen::email()` | адреса `local@label.tld` | к самому короткому локальному/меточному и первому TLD |
| `Gen::url()` | `http(s)://host.tld[/path]` URLs | toward `http://a.com` |
| `Gen::json($maxDepth)` | значение, кодируемое в формате JSON (null/bool/int/float/string/list/object) | внутри сгенерированной структуры |
 | `Gen::jsonString($maxDepth)` | текст `json_encode` `Gen::json()` | через дерево значений |
 | `Gen::regex($pattern)` / `Gen::stringMatching($pattern)` | строки, соответствующие подмножеству регулярных выражений (скомпилированные в комбинаторы) | более короткие/более простые совпадения (через скомпилированные деревья) |

 Числовые генераторы (`int*`, `float*`) **смещены по границам**: примерно одно из
 пяти возвращает значение края в пределах диапазона (`0`, `±1`, `min`, `max` для целых чисел; `0.0` или
`min` для чисел с плавающей запятой), где ошибки группируются, а не однородные. Сжатие
 не затрагивается. @@ЛИНИЯ@@
### Зависимые генераторы (` FlatMap`)
Когда один входной домен зависит от другого — список плюс действительный индекс в нем,
 размер плюс полезная нагрузка этого размера — `Gen::flatMap()` передает каждое сгенерированное значение
 в замыкание, которое возвращает произвольное значение для конечного значения. В отличие от защиты
 `Assume::that()`, никакие прогоны не отбрасываются, и оба уровня сжимаются: исходное значение
 сжимается (зависимое значение детерминированно регенерируется из
 начального числа прогона), затем зависимое значение сжимается, при этом источник остается фиксированным. @@ЛИНИЯ@@
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
### Отрисовка внутри тела (`Gen::draw`)
Если несколько зависимых значений усложняют вложенный ` FlatMap`, нарисуйте их внутри
 тела свойства с помощью `Gen::draw()`. Домен может зависеть от чего-либо, уже имеющего
 — параметров, предыдущих розыгрышей, промежуточных результатов:

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
Нарисованные значения сжимаются вместе с параметрами. Бегун записывает каждый розыгрыш
 на ленту повтора; когда свойство дает сбой, оно сжимает каждую записанную прорисовку
 через свое собственное дерево и повторно запускает тело с воспроизведением ленты по позиции.
 Параметр сжатия может изменить поток управления телом: прорисовки за
 конца ленты генерируются заново, а прорисовки меньшего участка, которого больше нет,
 отбрасываются. Отчет о контрпримерах рисуется как `draw#1`, `draw#2`, ... рядом с именованными параметрами
 (а `PROPERTY_VERBOSE` регистрирует их при каждом запуске).

 Две вещи, которые следует знать:

 - Повторный розыгрыш обслуживается по позиции и **не** повторно проверяется относительно
 (возможно, более узкого) произвольного нового потока управления — той же модели
, что и `gen()` быстрой проверки. Утверждайте, что на самом деле требуется телу, а не
, полагаясь на диапазон отрисовки после сжатия.
 - Поскольку во время сжатия лента может вырасти заново, аргумент завершения конечного дерева
 больше не применяется сам по себе; при наличии ничьих принятые шаги сжатия
 ограничены (по умолчанию 1000, при установке `maxShrinks` все равно выигрывает).

 `Gen::draw()` действителен только тогда, когда бегун выполняет тело свойства;
 куда угодно еще выбрасывает. Предпочитайте FlatMap для одного зависимого значения —
 сохраняет весь домен видимым в методе генераторов. @@ЛИНИЯ@@
### `Предположим::that()`
Отменяет текущий запуск, если предварительное условие не выполняется. Отброшенные прогоны
 не являются ни сбоями, ни успешными проверками. Предпочитайте его вместо `Gen::filter()`, когда уровень отклонения
 низок; когда более 90% запусков отбрасываются, бегун предупреждает
 о том, что генераторы, вероятно, неправильно настроены. @@ЛИНИЯ@@
```php
Assume::that($cap >= $baseSeconds);
```
### Ограничивающая термоусадочная работа
По умолчанию сжатие выполняется до тех пор, пока ни один меньший кандидат не выйдет из строя, повторно запуская свойство
 один раз для каждого принятого шага. Для дорогих свойств или очень больших входных данных вы
 можете ограничить количество принимаемых шагов сжатия с помощью `maxShrinks`:

```php
#[Property(runs: 200, maxShrinks: 25)]
```
`maxShrinks: null` (по умолчанию) означает отсутствие ограничения. `maxShrinks: 0` полностью отключает сжатие
 и сообщает исходный контрпример без изменений. Ограничение учитывает
 *принятые* шаги сжатия, а не выполнение тестов. @@ЛИНИЯ@@
### Написание своего произвольного
`Gen` охватывает общие случаи, но любое пространство значений доступно путем реализации
 [`ArbitraryInterface`](src/ArbitraryInterface.php) напрямую: `generate(Random)`
 возвращает [`Shrinkable`](src/Shrinkable.php) — нарисованное значение плюс ленивое дерево
 из меньших кандидатов, наиболее агрессивных в первую очередь, каждый из которых несет свое собственное поддерево.
 Рисуйте случайность только с помощью введенного `Random` (`int()`, `float()`,
 `bytes()`), чтобы начальные прогоны оставались воспроизводимыми. @@ЛИНИЯ@@
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
Пользовательский произвольный код используется как любой встроенный: верните его из генератора методом
, ключом которого является имя параметра. `Shrinkable::leaf($value)` создает терминальный узел
 (кандидатов нет); `Shrinkable::of($value, $closure)` присоединяет лениво вычисленные
 кандидаты; `Shrinkable::map($fn)` преобразует целое дерево. Сохраняйте каждую
 ветвь дерева конечной и никогда не давайте кандидата, равного его родителю —
, именно это гарантирует завершение сжатия. @@ЛИНИЯ@@
### Переопределения среды
Две переменные среды позволяют настроить запуск без изменения атрибутов — полезно в
 CI:

 | Переменная | Эффект |
 |---|---|
 | `PROPERTY_RUNS` | Положительное целое число, которое переопределяет счетчик запусков каждого свойства (в CI происходит набор номера). |
 | `PROPERTY_SEED` | Целочисленное начальное число, используемое для любого свойства, в атрибуте которого отсутствует `seed` (воспроизведение всего набора). Явный атрибут `seed` по-прежнему побеждает. |
 | `PROPERTY_VERBOSE` | Любое значение, кроме `''`/`0`, регистрирует сгенерированные аргументы каждого запуска — посмотрите, что именно воспроизведенное начальное значение передает свойству. |
 | `PROPERTY_DB` | Путь к каталогу, позволяющий воспроизвести регрессию (ниже). Не установлено означает, что функция отключена и ничего не пишется. | @@ЛИНИЯ@@
### Воспроизведение последней неудачи
Установите `PROPERTY_DB` в каталог, и фальсифицированное свойство запишет начальное значение, которое
 не удалось. При следующем запуске это начальное значение запускается повторно **первым** (если только атрибут
 не закрепляет собственное `начальное число`): о все еще неудачном начальном значении немедленно сообщается для быстрой обратной связи,
, а начальное число, которое больше не дает сбоев, забывается. Сохраняется только начальное значение — никогда не сгенерированные
 значения, которые могут быть объектами или замыканиями — поэтому повторный запуск начального числа
 воспроизводит ту же отрисовку. Хранилище представляет собой один небольшой файл для каждого свойства
 (`<sha1(id)>.seed`); добавьте каталог в `.gitignore`. @@ЛИНИЯ@@
### Явные примеры
Фиксированные входные данные закрепляют найденную ошибку как постоянный случай, который выполняется при каждом вызове,
 наряду со случайными. Объявите метод `<testMethod>Examples` (или назовите его
 через `#[Property(examples: 'method')]`), возвращающий кортежи позиционных аргументов; каждый
 запускается **перед** случайными вводами и сообщается дословно (не сжато — это уже
 минимальный случай, который вы закрепили) через `ExampleViolationException`. @@ЛИНИЯ@@
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
### Проверка раздачи
Свойство может пройти бесследно, если его генераторы никогда не достигнут интересных входных данных
. `Классифицировать` записывает метки за прогон; после полной передачи свойства бегун
 печатает долю запусков, попавших в каждую метку. @@ЛИНИЯ@@
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
Метка, записанная несколько раз в течение одного запуска, по-прежнему засчитывается один раз для этого запуска. @@ЛИНИЯ@@
### Обеспечение распространения
`Classify::cover()` повышает напечатанную подсказку до жесткого требования: метка
 должна встречаться, по крайней мере, в заданном проценте успешных запусков, или свойство
 **терпит неудачу** с `CoverageViolationException` — даже если каждый запуск прошел успешно.
 Используйте его, чтобы сделать невозможными пустые проходы в CI. @@ЛИНИЯ@@
```php
#[Property(runs: 500)]
public function holds(int $n): void
{
    Classify::cover($n % 2 === 0, 'even', 30.0); // fail if < 30% of runs are even
    // ... assertions ...
}
```
Отброшенные прогоны («Предположим::that()») исключаются из знаменателя. Свойство
, все прогоны которого отброшены, полностью не соответствует требованиям к покрытию. @@ЛИНИЯ@@
### Выборка генератора
`Gen::sample()` охотно генерирует значения из любых произвольных значений для фиксированного начального числа —
 быстрый способ увидеть, что производит генератор (он возвращает значения, а не произвольный
). @@ЛИНИЯ@@
```php
Gen::sample(Gen::intBetween(1, 6), count: 5, seed: 42); // [3, 1, 6, 6, 2]
```
`Gen::sampleShrinks()` делает то же самое для дерева сжатия: генерирует одно значение
 и выводит список первых кандидатов на прямое сжатие — это самый быстрый способ проверить
, что произвольное произвольное значение сжимается так, как вы задумали. @@ЛИНИЯ@@
```php
Gen::sampleShrinks(Gen::intBetween(0, 100), seed: 1);
// ['value' => 87, 'shrinks' => [0, 44, 66, 77, 82, 85, 86]]
```
### Рецепты
Зависимые значения без отбрасывания — строить, не фильтровать:

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
Сохраняйте разветвление ветвей небольшими (размеры массива ограничены): ширина умножается на
 на каждом уровне вложенности. @@ЛИНИЯ@@
### Тестирование на основе состояния/модели
Некоторые ошибки проявляются только в *последовательности* операций — счетчик, который
 переполняется после N приращений, кэш, возвращающий устаревшие данные, стек, в котором
 теряет порядок. Тестирование на основе модели генерирует случайные последовательности команд,
 запускает каждую из них в реальной системе, одновременно отражая ее в упрощенной модели, а
 в случае неудачи **сжимает последовательность** до самой короткой, которая все же нарушается.

 Реализуйте [`Command`](src/StateMachine/Command.php) — четыре чисто
 обязанности плюс метку:

 | Метод | Цель |
 |---|---|
 | `preCondition(смешанная $модель): bool` | Может ли эта команда выполняться в текущем состоянии модели? Генерация шлюзов и, при воспроизведении, выполняется ли команда или она пропускается. |
 | `nextState(смешанная $модель): смешанная` | Ожидаемое следующее состояние модели (чистое; возвращает новую модель, никогда не мутирует). |
 | `run(смешанная $модель, смешанная $система): смешанный` | Выполнить на тестируемой системе; вернуть наблюдаемый результат. |
 | `postCondition(смешанная $модель, смешанный $результат): bool` | Сравните результат с моделью предварительного состояния. Чтобы выполнить фальсификацию, верните `false` (или throw). |
 | `__toString(): строка` | Метка, используемая в трассировке контрпримера. |

 `Gen::commands($initialModel, $commandGenerators)` строит допустимые последовательности (каждый шаг
 добавляет команду, предварительное условие которой выполнено, а затем продвигает модель), а
 `StateMachine::check()` сравнивает сгенерированную последовательность с новой системой:

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
Сжатие удаляет целые блоки команд (вплоть до одного, поэтому неудачный шаг
 в середине изолируется), а затем упрощает параметры каждой команды
 через собственное дерево. Поскольку бегун перепроверяет каждое предварительное условие и пропускает
 любой пропущенный шаг, признанный недействительным, каждая сжатая последовательность остается верной. Контрпример
 отображается как читаемая трассировка, а невыполненное постусловие выдает
 [`PostconditionViolation`](src/StateMachine/PostconditionViolation.php), называя
 шаг:

```
Property falsified after 7 successful run(s); seed=42
  Original: sequence=[Push(3), Pop(), Push(5), Push(1), Pop(), Pop()]
  Shrunk:   sequence=[Push(0), Push(1), Pop()] (9 shrink step(s))
  Failure:  Postcondition failed at step 3 for command Pop(); sequence: [Push(0), Push(1), Pop()]
```
Полный пример
 см. в [`examples/state_machine.php`](examples/state_machine.php). @@ЛИНИЯ@@
## Безопасность
Этот пакет выполняет тестовые методы посредством отражения (для чтения атрибута `#[Property]`
 и вызова метода генераторов) и через конвейер Testo. Резервный перехватчик
 Testo — PropertyInterceptor. Он
 сам не выполняет никаких операций ввода-вывода, SQL, оболочки или сетевых операций. Случайные значения
 генерируются с помощью PHP-движка MT19937, на основе полученного начального числа; не полагайтесь на
 их в криптографических целях. @@ЛИНИЯ@@
## Примеры
См. [examples/](examples/) для работоспособных сценариев.

 | Скрипт | Шоу | Нужен сервер? |
 |---|---|---|
 | `basic.php` | свойство, которое сохраняется, свойство, которое фальсифицировано, и сжатие на основе дерева | Нет |
 | `property_test.php` | каноническое использование `#[Property]` в качестве реального тестового примера Testo | Нет |
 | `генераторы.php` | `sample`, граничное смещение, `uuid`, `datetime`, `dictOf`, `record`, `latMap` | Нет |
 | `state_machine.php` | тестирование с отслеживанием состояния/моделью: `Command`, `Gen::commands()`, `StateMachine::check()` | Нет | @@ЛИНИЯ@@
## Разработка
На хосте нет PHP/Composer. Запускайте команды в Docker через образ `composer:2`:

```bash
docker run --rm -v "$PWD":/app -w /app composer:2 composer install
docker run --rm -v "$PWD":/app -w /app composer:2 composer build
docker run --rm -v "$PWD":/app -w /app composer:2 composer cs:fix
docker run --rm -v "$PWD":/app -w /app composer:2 composer test
docker run --rm -v "$PWD":/app -w /app composer:2 composer release-check
```
Или с помощью Make:

```bash
make install
make build
make cs-fix
make test
make test-coverage
make mutation
make release-check
```
`make test-coverage` и `makemutation` загружают `pcov` внутри контейнера
 `composer:2`, поскольку базовый образ не имеет драйвера покрытия. @@ЛИНИЯ@@
## Лицензия
[BSD-3-пункт](LICENSE.md)
