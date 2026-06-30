# rasuvaeff/property-testing

[![Latest Stable Version](https://poser.pugx.org/rasuvaeff/property-testing/v)](https://packagist.org/packages/rasuvaeff/property-testing)
[![Total Downloads](https://poser.pugx.org/rasuvaeff/property-testing/downloads)](https://packagist.org/packages/rasuvaeff/property-testing)
[![Build](https://github.com/rasuvaeff/property-testing/actions/workflows/build.yml/badge.svg)](https://github.com/rasuvaeff/property-testing/actions/workflows/build.yml)
[![Static analysis](https://github.com/rasuvaeff/property-testing/actions/workflows/static-analysis.yml/badge.svg)](https://github.com/rasuvaeff/property-testing/actions/workflows/static-analysis.yml)
[![Psalm level](https://img.shields.io/badge/psalm-level_1-blue.svg)](https://github.com/rasuvaeff/property-testing/actions/workflows/static-analysis.yml)
[![PHP](https://img.shields.io/packagist/dependency-v/rasuvaeff/property-testing/php)](https://packagist.org/packages/rasuvaeff/property-testing)
[![License](https://img.shields.io/badge/license-BSD--3--Clause-blue.svg)](LICENSE.md)

Property-based testing for PHP 8.3+, built as a plugin for the
[Testo](https://github.com/php-testo/testo) testing framework. Generate hundreds
of random inputs per test, find the failing one, and shrink it to a minimal
counterexample you can actually read.

> Using an AI coding assistant? [llms.txt](llms.txt) contains a compact API reference you can share with the model.

## Requirements

- PHP 8.3+
- `ext-mbstring`
- `ext-random`
- [`testo/testo`](https://packagist.org/packages/testo/testo) `^0.10.25 || ^1.0`

## Installation

```bash
composer require --dev rasuvaeff/property-testing
```

No plugin registration is needed: the `#[Property]` attribute self-registers
with Testo through the framework's interceptor discovery.

## Usage

Mark a test method with `#[Property]` and point it at a generators method that
maps each parameter name to a `Gen` factory.
The runner generates random arguments, runs the property `runs` times, and on
the first failure shrinks the counterexample to a minimal one.

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
    private function delayGenerators(): array
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

On failure, the counterexample is rendered into the test output:

```
Property falsified after 246 successful run(s); seed=7382910
  Original: maxAttempts=17, baseSeconds=91, cap=847, attempts=23
  Shrunk:   maxAttempts=1, baseSeconds=848, cap=847, attempts=1 (12 shrink step(s))
```

Reproduce the exact run by passing the reported seed back to the attribute:

```php
#[Property(runs: 500, seed: 7382910, generators: 'delayGenerators')]
```

### Why generators are in a separate method

PHP attribute arguments must be constant expressions, so `#[Given('x', Gen::int())]`
is not expressible. Instead name a method that returns
`array<string, ArbitraryInterface>` keyed by parameter name. When the `generators`
argument is omitted the runner falls back to a method named `<testMethod>Generators`.

### Generators

| Factory | Produces | Shrinks |
|---|---|---|
| `Gen::int()` | `IntArbitrary`, `PHP_INT_MIN..PHP_INT_MAX` | toward `0` |
| `Gen::intBetween($min, $max)` | `IntArbitrary`, `[$min, $max]` | toward `0`, clamped to range |
| `Gen::intPositive()` | `IntArbitrary`, `1..PHP_INT_MAX` | toward `1` |
| `Gen::float()` | `FloatArbitrary`, `[0.0, 1.0)` | toward `0.0` |
| `Gen::floatBetween($min, $max)` | `FloatArbitrary`, `[$min, $max]` | toward `0.0`, clamped to range |
| `Gen::bool()` | `BoolArbitrary`, `true` / `false` | `true` -> `false` |
| `Gen::string()` | `StringArbitrary`, Unicode, length 0..100 | toward `''`, then by length, then each character toward `a` |
| `Gen::stringAscii()` | `StringArbitrary`, printable ASCII, length 0..100 | toward `''`, then by length, then each character toward `a` |
| `Gen::stringOf($min, $max)` | `StringArbitrary`, Unicode, bounded length | toward `''`, then by length, then each character toward `a` |
| `Gen::arrayOf($element)` | `ArrayArbitrary`, lists of `$element`, size 0..100 | toward `[]`, then by length, then each element |
| `Gen::nonEmptyArrayOf($element)` | `ArrayArbitrary`, non-empty lists | by length (never below 1), then each element |
| `Gen::dictOf($key, $value)` | `DictionaryArbitrary`, maps with keys from `$key` (int/string) and values from `$value`, size 0..100 | toward `[]`, then by size, then each value (keys fixed) |
| `Gen::record($shape)` | `RecordArbitrary`, fixed-shape map `['field' => $arb, ...]` | each field via its arbitrary, key set fixed |
| `Gen::oneOf(...$values)` | `OneOfArbitrary`, one of the given values | each distinct other value |
| `Gen::nullable($inner)` | `NullableArbitrary`, `null` or an `$inner` value | prefers `null` |
| `Gen::map($inner, $fn)` | `MappedArbitrary`, `$inner` transformed by `$fn` | no shrinking (mapping may not be invertible) |
| `Gen::filter($inner, $predicate)` | `FilteredArbitrary`, `$inner` values satisfying `$predicate` | delegates, keeping predicate-satisfying candidates |
| `Gen::tuple(...$elements)` | `TupleArbitrary`, fixed-arity tuple, one value per element | each position via its element, arity fixed |
| `Gen::frequency($pairs)` | `FrequencyArbitrary`, weighted choice over `[weight, arbitrary]` pairs | delegates to the inner arbitraries |

### `Assume::that()`

Discards the current run when a precondition does not hold. Discarded runs are
neither failures nor successful checks. Prefer it over `Gen::filter()` when the
rejection rate is low; when more than 90% of runs are discarded the runner warns
that the generators are likely misconfigured.

```php
Assume::that($cap >= $baseSeconds);
```

### Bounding shrink work

By default shrinking runs until no smaller candidate still fails, re-running the
property once per accepted step. On expensive properties or very large inputs you
can cap the number of accepted shrink steps with `maxShrinks`:

```php
#[Property(runs: 200, maxShrinks: 25)]
```

`maxShrinks: null` (the default) means no cap. `maxShrinks: 0` disables shrinking
entirely and reports the original counterexample unchanged. The cap counts
*accepted* shrink steps, not test executions.

### Writing your own arbitrary

`Gen` covers common cases, but any value space is reachable by implementing
[`ArbitraryInterface`](src/ArbitraryInterface.php) directly: `generate(Random)`
draws one value, and `shrink(mixed)` yields progressively smaller candidates
(most aggressive first). Draw randomness only through the injected `Random`
(`int()`, `float()`, `bytes()`) so seeded runs stay reproducible.

```php
use Rasuvaeff\PropertyTesting\ArbitraryInterface;
use Rasuvaeff\PropertyTesting\Random;

/**
 * Even integers in [0, $max], shrinking toward 0 in even steps.
 */
final readonly class EvenArbitrary implements ArbitraryInterface
{
    public function __construct(private int $max = 1000) {}

    #[\Override]
    public function generate(Random $random): int
    {
        return $random->int(0, intdiv($this->max, 2)) * 2;
    }

    #[\Override]
    public function shrink(mixed $value): iterable
    {
        if (!is_int($value) || $value === 0) {
            return;
        }

        $half = intdiv($value, 4) * 2; // stay even

        yield 0;

        if ($half !== 0 && $half !== $value) {
            yield $half;
        }
    }
}
```

A custom arbitrary is used like any built-in: return it from the generators
method keyed by parameter name. Keep `shrink()` terminating — never yield a
candidate equal to the input.

## Security

This package executes test methods via reflection (to read the `#[Property]`
attribute and invoke the generators method) and through Testo's pipeline. The
fallback Testo interceptor is `PropertyInterceptor`. It
performs no I/O, SQL, shell, or network operations itself. Random values are
generated with PHP's MT19937 engine seeded by the reported seed; do not rely on
them for cryptographic purposes.

## Examples

See [examples/](examples/) for runnable scripts.

| Script | Shows | Needs server? |
|---|---|---|
| `basic.php` | a property that holds, one that is falsified, and shrinking | No |

## Development

No PHP/Composer on the host. Run commands in Docker via the `composer:2` image:

```bash
docker run --rm -v "$PWD":/app -w /app composer:2 composer install
docker run --rm -v "$PWD":/app -w /app composer:2 composer build
docker run --rm -v "$PWD":/app -w /app composer:2 composer cs:fix
docker run --rm -v "$PWD":/app -w /app composer:2 composer test
docker run --rm -v "$PWD":/app -w /app composer:2 composer release-check
```

Or with Make:

```bash
make install
make build
make cs-fix
make test
make test-coverage
make mutation
make release-check
```

`make test-coverage` and `make mutation` bootstrap `pcov` inside the
`composer:2` container because the base image has no coverage driver.

## License

[BSD-3-Clause](LICENSE.md)
