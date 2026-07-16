# rasuvaeff/property-testing

[English version](README.md)

Property-based testing для PHP 8.3+ как plugin для Testo. Пакет генерирует
случайные входы, находит counterexample и shrink'ает его до читаемого
минимального случая.

## Требования

- PHP 8.3+, `ext-mbstring`, `ext-random`;
- `testo/testo` `^0.10.25 || ^1.0`.

## Установка

```bash
composer require --dev rasuvaeff/property-testing
```

## Использование

Отметьте method атрибутом `#[Property]`; public static method
`<testMethod>Generators()` возвращает `array<string, ArbitraryInterface>`,
где ключи совпадают с именами аргументов. PHP attributes принимают только
constant expressions, поэтому `Gen::*` нельзя передать inline.

```php
#[Property(runs: 300)]
public function additionIsCommutative(int $left, int $right): void
{
    Assert::same($left + $right, $right + $left);
}

public static function additionIsCommutativeGenerators(): array
{
    return ['left' => Gen::int(), 'right' => Gen::int()];
}
```

`Gen` предоставляет базовые, string, collection и domain generators, а также
`map`, `flatMap`, `filter`, `tuple`, `frequency`, `regex`, `json` и
`commands`. Стройте зависимые domains через `flatMap`, а не filter.
`Gen::draw()` разрешён только внутри property run; draws участвуют в shrinking
и отражаются в counterexample как `draw#N`. `Assume::that(false)` discards run,
а не отмечает его как success.

Для обязательных cases применяйте `#[Property(examples: ...)]` или
`<testMethod>Examples()`. `Classify::collect()`/`cover()` помогают проверять
распределение, `PROPERTY_SEED` воспроизводит failure. `StateMachine::check()`
и `Gen::commands()` покрывают stateful/model-based tests.

## Безопасность

Не передавайте production secrets в generators или логи counterexamples.

## Примеры

См. [examples/](examples/) и полную API-справку в [README.md](README.md).

## Разработка

```bash
docker run --rm -v "$PWD":/app -w /app composer:2 composer build
```

## Лицензия

BSD-3-Clause. См. [LICENSE.md](LICENSE.md).
