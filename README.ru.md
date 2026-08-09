# rasuvaeff/property-testing

[![Latest Stable Version](https://poser.pugx.org/rasuvaeff/property-testing/v)](https://packagist.org/packages/rasuvaeff/property-testing)
[![Total Downloads](https://poser.pugx.org/rasuvaeff/property-testing/downloads)](https://packagist.org/packages/rasuvaeff/property-testing)
[![Build](https://github.com/rasuvaeff/property-testing/actions/workflows/build.yml/badge.svg)](https://github.com/rasuvaeff/property-testing/actions/workflows/build.yml)
[![Static analysis](https://github.com/rasuvaeff/property-testing/actions/workflows/static-analysis.yml/badge.svg)](https://github.com/rasuvaeff/property-testing/actions/workflows/static-analysis.yml)
[![License](https://img.shields.io/badge/license-BSD--3--Clause-blue.svg)](LICENSE.md)
[![Docs](https://github.com/rasuvaeff/property-testing/actions/workflows/docs.yml/badge.svg)](https://rasuvaeff.github.io/property-testing/)

[English version](README.md)

> **Пакет заморожен.** `2.8.1` — последний функциональный релиз. Пакет помечен
> abandoned с заменой на
> [`rasuvaeff/property-testing-testo`](https://packagist.org/packages/rasuvaeff/property-testing-testo).
> Здесь выходят только security-фиксы; версии 3.0 не будет. Вся дальнейшая
> работа идёт в трёх пакетах ниже.

Property-based тестирование для PHP 8.3–8.5, реализованное как плагин для
тест-фреймворка [Testo](https://github.com/php-testo/testo). Движок и
интеграция с фреймворком разделены на отдельные пакеты, поэтому проект больше
не тянет тест-фреймворк, которым не пользуется.

## Куда переехал код

| Пакет | Содержимое |
|---|---|
| [`rasuvaeff/property-testing-core`](https://github.com/rasuvaeff/property-testing-core) | Генераторы, runner, shrinking, корпус, listeners, state machine — без зависимости от тест-фреймворка |
| [`rasuvaeff/property-testing-testo`](https://github.com/rasuvaeff/property-testing-testo) | `#[Property]` и всё Testo-специфичное |
| [`rasuvaeff/property-testing-phpunit`](https://github.com/rasuvaeff/property-testing-phpunit) | Fluent API `forAll()` для PHPUnit |

## Миграция

| Что используете | Чем заменить dev-зависимость | Правки PHP-кода |
|---|---|---|
| `#[Property]` под Testo | `rasuvaeff/property-testing-testo` | **никаких** |
| Свой harness / CLI-скрипт | `rasuvaeff/property-testing-core` | никаких для публичного API; код, лазивший в `@internal`-классы, правит импорты |
| PHPUnit | `rasuvaeff/property-testing-phpunit` | новый пакет, fluent API |

**Никаких** относится к тому, что 2.x документировала как публичное — FQCN,
конвенции и переменные из списка ниже. `@internal`-классы под гарантию не
подпадают: часть из них при разделении переименована или повышена до `@api`,
и руководство по ссылке в конце этой секции перечисляет их все.

Для пользователей Testo вся миграция — одна команда:

```bash
composer remove --dev rasuvaeff/property-testing
composer require --dev "rasuvaeff/property-testing-testo:^0.1" -W
```

Сначала `composer remove` — обязательно: core объявляет
`conflict: {"rasuvaeff/property-testing": "*"}`, потому что оба пакета
поставляют классы в неймспейс `Rasuvaeff\PropertyTesting`. Смешанная установка
намеренно нерешаема, а не молча даёт дубли классов в autoload. `-W` разрешает
composer'у поднять `testo/testo` до версии, которую требует адаптер
(`^0.10.39 || ^1.0`).

Что переезжает без изменений:

- все публичные FQCN — `Rasuvaeff\PropertyTesting\Gen`, `ArbitraryInterface`,
  `Shrinkable`, `Assume`, `Classify`, `Property`, state machine и публичные
  исключения;
- конвенции `<method>Generators()` и `<method>Examples()`;
- `PROPERTY_SEED`, `PROPERTY_RUNS`, `PROPERTY_DB`, `PROPERTY_VERBOSE`;
- формат сообщения о контрпримере;
- корпус регрессий на диске — корпус, записанный 2.8, читается `-testo`
  (тот же `FORMAT_VERSION`, побайтово совместимый JSON);
- детерминизм seed (`SEQUENCE_EPOCH` не менялся).

Полное руководство, включая пути для своего harness и PHPUnit, —
[MIGRATION.md в property-testing-core](https://github.com/rasuvaeff/property-testing-core/blob/master/MIGRATION.md).

## Документация

[Сайт документации](https://rasuvaeff.github.io/property-testing/) описывает
эту замороженную 2.x-линию. Главы руководства по-прежнему верно описывают
движок — FQCN и поведение те же, что поставляют core и `-testo`, — но сайт не
обновляется тем, что выходит после 2.8.1. Новое семейство документирует себя в
`README.md`/`README.ru.md`, `llms.txt` и `examples/` каждого пакета.

[llms.txt](llms.txt) в этом репозитории остаётся компактным API-справочником по
2.x-линии.

## Установка

Пакет по-прежнему устанавливается и работает; новым проектам его брать не
нужно:

```bash
composer require --dev rasuvaeff/property-testing
```

## Примеры

В [examples/](examples/) остались исполняемые 2.x-скрипты (`basic.php`,
`property_test.php`, `generators.php`, `state_machine.php`); серверов ни один
из них не требует.

## Безопасность

Пакет исполняет тест-методы через рефлексию (чтобы прочитать атрибут
`#[Property]` и вызвать метод генераторов) и через пайплайн Testo. Fallback
Testo-интерцептор — `PropertyInterceptor`. Сам он не выполняет ни I/O, ни SQL,
ни shell, ни сетевых операций. Случайные значения генерируются движком MT19937
из PHP, засеянным сообщённым seed; на криптографическую стойкость не
рассчитывать.

Security-фиксы — единственные изменения, которые эта линия ещё получает; они
выходят здесь patch-релизами и, если тот же код есть в новом семействе, в
соответствующем пакете.

## Разработка

PHP/Composer на хосте нет. Команды запускаются в Docker через образ
`composer:2`:

```bash
docker run --rm -v "$PWD":/app -w /app composer:2 composer install
docker run --rm -v "$PWD":/app -w /app composer:2 composer build
```

Или через Make: `make install`, `make build`, `make cs-fix`, `make test`.

## Лицензия

[BSD-3-Clause](LICENSE.md)
