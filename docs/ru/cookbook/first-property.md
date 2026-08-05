---
title: "Рецепт: первое property"
---

# Рецепт: первое property

Проходим путь от пустого тест-класса до падающего property и обратно к
зелёному на примере [`property_test.php`](https://github.com/rasuvaeff/property-testing/blob/master/examples/property_test.php).

## 1. Установка и Testo-тест

```
composer require --dev rasuvaeff/property-testing
```

Property — это обычный метод `#[Test]`-класса, помеченный `#[Property]`
вместо утверждений на захардкоженных значениях:

```php
use Rasuvaeff\PropertyTesting\Gen;
use Rasuvaeff\PropertyTesting\Property;
use Testo\Assert;
use Testo\Test;

#[Test]
final class ListReversalProperties
{
    #[Property(runs: 200)]
    public function reversingTwiceRestoresTheList(array $xs): void
    {
        Assert::same(array_reverse(array_reverse($xs)), $xs);
    }

    /** @return array<string, \Rasuvaeff\PropertyTesting\ArbitraryInterface> */
    public static function reversingTwiceRestoresTheListGenerators(): array
    {
        return ['xs' => Gen::arrayOf(Gen::intBetween(-100, 100))];
    }
}
```

Две вещи делают это property, а не example-based тестом:

- Метод принимает параметр (`array $xs`) вместо захардкоженного входа.
- Соседний метод с именем `<methodName>Generators` (или явно указанный через
  `#[Property(generators: '...')]`) возвращает по одному `ArbitraryInterface`
  на параметр, ключ — имя параметра. См. [Генераторы](/ru/generators/index)
  — аргументы атрибутов должны быть constant expressions, поэтому генератор
  не может жить прямо внутри `#[Property(...)]`.

Запуск:

```bash
docker run --rm -v "$PWD":/app -w /app composer:2 vendor/bin/testo
```

`runs: 200` — это 200 *успешных* проверок; отброшенные попытки (см.
[Assume vs filter](/ru/controlling-runs/assume-vs-filter)) в этот счёт не
идут.

## 2. Написать падающее — намеренно

Замените утверждение на неверный инвариант, чтобы увидеть фальсификацию
property целиком:

```php
#[Property(runs: 200)]
public function everyIntIsEven(int $n): void
{
    Assert::true($n % 2 === 0);
}

/** @return array<string, \Rasuvaeff\PropertyTesting\ArbitraryInterface> */
public static function everyIntIsEvenGenerators(): array
{
    return ['n' => Gen::intBetween(0, 1000)];
}
```

Вывод выглядит так:

```
Property falsified after 3 successful run(s); seed=918273645
  Original: n=847
  Shrunk:   n=1 (4 shrink step(s), 9 trial(s))
```

Читайте справа налево:

- **`seed`** — верните его через `#[Property(seed: 918273645)]`, чтобы
  реплеить ровно этот прогон; см. [Воспроизведение по seed](/ru/cookbook/reproducing-with-seed).
- **`Original`** — первый упавший вход, без изменений.
- **`Shrunk`** — наименьший вход, который шринкер смог найти всё ещё
  падающим, достигнутый жадным спуском по shrink-дереву значения. `1` —
  действительно минимальное нечётное int, до которого шринкер может дойти,
  шринкая к `0`. См. [Shrinking](/ru/shrinking), как работает спуск и почему
  он завершается.
- **`shrink step(s)` / `trial(s)`** — принятые спуски против всех
  опробованных кандидатов (принятых + отклонённых).

## 3. Снова зелёный

Исправьте утверждение (или код под тестом) и перезапустите. Проходящее
property ничего дополнительно не печатает — Testo сообщает о нём как о любом
другом прошедшем тесте. Отдельного «режима property» для проверки нет; вывод
выше — единственный дополнительный сигнал, который этот пакет добавляет
поверх обычного падения assertion.

## Куда дальше

- [Концепции](/ru/intro/concepts) — словарь (arbitrary, shrinking,
  counterexample), которым эта страница пользовалась без определения.
- [Обзор генераторов](/ru/generators/index) — полный каталог `Gen::*`.
- [Воспроизведение по seed](/ru/cookbook/reproducing-with-seed) — когда
  встретите первое реальное падение и понадобится отладить его, а не просто
  прочитать однострочную сводку.
