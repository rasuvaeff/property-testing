---
title: "Gen"
description: "Gen — class в справочнике API property-testing."
---

<!-- АВТОГЕНЕРАЦИЯ: docs/scripts/generate-api.mjs, проход рефлексии по src/ (docs/scripts/reflect-api.php) — не редактировать вручную. -->

# `Gen`

`Rasuvaeff\PropertyTesting\Gen`

**Класс** — [Исходник](https://github.com/rasuvaeff/property-testing/blob/master/src/Gen.php)

## Методы

### int()

```php
static int(): Arbitrary\IntArbitrary
```

### intBetween()

```php
static intBetween(int $min, int $max): Arbitrary\IntArbitrary
```

- `$min` — undefined
- `$max` — undefined

### intPositive()

```php
static intPositive(): Arbitrary\IntArbitrary
```

### float()

```php
static float(): Arbitrary\FloatArbitrary
```

### floatBetween()

```php
static floatBetween(float $min, float $max): Arbitrary\FloatArbitrary
```

- `$min` — undefined
- `$max` — undefined

### bool()

```php
static bool(): Arbitrary\BoolArbitrary
```

### string()

```php
static string(): Arbitrary\StringArbitrary
```

### stringAscii()

```php
static stringAscii(): Arbitrary\StringArbitrary
```

### stringOf()

```php
static stringOf(int $minLength, int $maxLength): Arbitrary\StringArbitrary
```

- `$minLength` — undefined
- `$maxLength` — undefined

### char()

```php
static char(): Arbitrary\StringArbitrary
```

### stringFrom()

```php
static stringFrom(
    string $alphabet,
    int $minLength,
    int $maxLength,
): Arbitrary\CharsetStringArbitrary
```

- `$alphabet` — undefined
- `$minLength` — undefined
- `$maxLength` — undefined

### bytes()

```php
static bytes(int $minLength, int $maxLength): Arbitrary\BytesArbitrary
```

- `$minLength` — undefined
- `$maxLength` — undefined

### arrayOf()

```php
static arrayOf(
    ArbitraryInterface $element,
    int $minSize,
    int $maxSize,
): Arbitrary\ArrayArbitrary
```

- `$element` — undefined
- `$minSize` — undefined
- `$maxSize` — undefined

### nonEmptyArrayOf()

```php
static nonEmptyArrayOf(
    ArbitraryInterface $element,
    int $maxSize,
): Arbitrary\ArrayArbitrary
```

- `$element` — undefined
- `$maxSize` — undefined

### uniqueArrayOf()

```php
static uniqueArrayOf(
    ArbitraryInterface $element,
    int $minSize,
    int $maxSize,
): Arbitrary\UniqueArrayArbitrary
```

- `$element` — undefined
- `$minSize` — undefined
- `$maxSize` — undefined

### dictOf()

```php
static dictOf(
    ArbitraryInterface $key,
    ArbitraryInterface $value,
    int $minSize,
    int $maxSize,
): Arbitrary\DictionaryArbitrary
```

- `$key` — undefined
- `$value` — undefined
- `$minSize` — undefined
- `$maxSize` — undefined

### record()

```php
static record(array $shape): Arbitrary\RecordArbitrary
```

- `$shape` — undefined

### oneOf()

```php
static oneOf(mixed $values): Arbitrary\OneOfArbitrary
```

- `$values` — undefined

### elements()

```php
static elements(array $values): Arbitrary\OneOfArbitrary
```

- `$values` — undefined

### constant()

```php
static constant(mixed $value): Arbitrary\ConstantArbitrary
```

- `$value` — undefined

### enum()

```php
static enum(string $enum): Arbitrary\OneOfArbitrary
```

- `$enum` — undefined

### floatSpecial()

```php
static floatSpecial(): Arbitrary\OneOfArbitrary
```

### intRange()

```php
static intRange(int $min, int $max): Arbitrary\FlatMappedArbitrary
```

- `$min` — undefined
- `$max` — undefined

### recursive()

```php
static recursive(
    ArbitraryInterface $leaf,
    Closure $wrap,
    int $maxDepth,
): ArbitraryInterface
```

- `$leaf` — undefined
- `$wrap` — undefined
- `$maxDepth` — undefined

### nullable()

```php
static nullable(ArbitraryInterface $inner): Arbitrary\NullableArbitrary
```

- `$inner` — undefined

### map()

```php
static map(ArbitraryInterface $inner, Closure $map): Arbitrary\MappedArbitrary
```

- `$inner` — undefined
- `$map` — undefined

### flatMap()

```php
static flatMap(
    ArbitraryInterface $inner,
    Closure $flatMap,
): Arbitrary\FlatMappedArbitrary
```

- `$inner` — undefined
- `$flatMap` — undefined

### filter()

```php
static filter(
    ArbitraryInterface $inner,
    Closure $predicate,
): Arbitrary\FilteredArbitrary
```

- `$inner` — undefined
- `$predicate` — undefined

### draw()

```php
static draw(ArbitraryInterface $arbitrary): mixed
```

- `$arbitrary` — undefined

### tuple()

```php
static tuple(ArbitraryInterface $elements): Arbitrary\TupleArbitrary
```

- `$elements` — undefined

### frequency()

```php
static frequency(iterable $pairs): Arbitrary\FrequencyArbitrary
```

- `$pairs` — undefined

### uuid()

```php
static uuid(): Arbitrary\UuidArbitrary
```

### datetime()

```php
static datetime(
    ?DateTimeImmutable $min,
    ?DateTimeImmutable $max,
): Arbitrary\DateTimeArbitrary
```

- `$min` — undefined
- `$max` — undefined

### ipv4()

```php
static ipv4(): Arbitrary\MappedArbitrary
```

### email()

```php
static email(): Arbitrary\MappedArbitrary
```

### url()

```php
static url(): Arbitrary\MappedArbitrary
```

### json()

```php
static json(int $maxDepth): ArbitraryInterface
```

- `$maxDepth` — undefined

### jsonString()

```php
static jsonString(int $maxDepth): Arbitrary\MappedArbitrary
```

- `$maxDepth` — undefined

### regex()

```php
static regex(string $pattern, int $maxRepeat): ArbitraryInterface
```

- `$pattern` — undefined
- `$maxRepeat` — undefined

### stringMatching()

```php
static stringMatching(string $pattern, int $maxRepeat): ArbitraryInterface
```

- `$pattern` — undefined
- `$maxRepeat` — undefined

### commands()

```php
static commands(
    mixed $initialModel,
    array $commandGenerators,
    int $minLength,
    int $maxLength,
): Arbitrary\CommandSequenceArbitrary
```

- `$initialModel` — undefined
- `$commandGenerators` — undefined
- `$minLength` — undefined
- `$maxLength` — undefined

### sample()

```php
static sample(
    ArbitraryInterface $arbitrary,
    int $count,
    int $seed,
): array
```

- `$arbitrary` — undefined
- `$count` — undefined
- `$seed` — undefined

### sampleShrinks()

```php
static sampleShrinks(
    ArbitraryInterface $arbitrary,
    int $seed,
    int $limit,
): array
```

- `$arbitrary` — undefined
- `$seed` — undefined
- `$limit` — undefined

