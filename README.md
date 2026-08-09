# rasuvaeff/property-testing

[![Latest Stable Version](https://poser.pugx.org/rasuvaeff/property-testing/v)](https://packagist.org/packages/rasuvaeff/property-testing)
[![Total Downloads](https://poser.pugx.org/rasuvaeff/property-testing/downloads)](https://packagist.org/packages/rasuvaeff/property-testing)
[![Build](https://github.com/rasuvaeff/property-testing/actions/workflows/build.yml/badge.svg)](https://github.com/rasuvaeff/property-testing/actions/workflows/build.yml)
[![Static analysis](https://github.com/rasuvaeff/property-testing/actions/workflows/static-analysis.yml/badge.svg)](https://github.com/rasuvaeff/property-testing/actions/workflows/static-analysis.yml)
[![License](https://img.shields.io/badge/license-BSD--3--Clause-blue.svg)](LICENSE.md)
[![Docs](https://github.com/rasuvaeff/property-testing/actions/workflows/docs.yml/badge.svg)](https://rasuvaeff.github.io/property-testing/)

[Русская версия](README.ru.md)

> **This package is frozen.** `2.8.1` is the last functional release. It is
> marked abandoned in favour of
> [`rasuvaeff/property-testing-testo`](https://packagist.org/packages/rasuvaeff/property-testing-testo).
> Only security fixes will be published here; there will be no 3.0.
> All new work happens in the three packages below.

Property-based testing for PHP 8.3–8.5, built as a plugin for the
[Testo](https://github.com/php-testo/testo) testing framework. The engine and
the framework integration have been split into separate packages, so a project
no longer pulls a test framework it does not use.

## Where the code went

| Package | Contents |
|---|---|
| [`rasuvaeff/property-testing-core`](https://github.com/rasuvaeff/property-testing-core) | Generators, runner, shrinking, corpus, listeners, state machine — no test framework dependency |
| [`rasuvaeff/property-testing-testo`](https://github.com/rasuvaeff/property-testing-testo) | `#[Property]` and everything Testo-specific |
| [`rasuvaeff/property-testing-phpunit`](https://github.com/rasuvaeff/property-testing-phpunit) | Fluent `forAll()` API for PHPUnit |

## Migration

| You use | Replace the dev dependency with | PHP code changes |
|---|---|---|
| `#[Property]` under Testo | `rasuvaeff/property-testing-testo` | **none** |
| Your own harness / a CLI script | `rasuvaeff/property-testing-core` | none for the public API; code that reached into the `@internal` classes has imports to update |
| PHPUnit | `rasuvaeff/property-testing-phpunit` | new package, fluent API |

The **none** applies to what 2.x documented as public — the FQCNs, conventions
and variables listed below. `@internal` classes are not covered: some were
renamed or promoted during the split, and the guide linked at the end of this
section maps every one of them.

For Testo users the whole migration is one command:

```bash
composer remove --dev rasuvaeff/property-testing
composer require --dev "rasuvaeff/property-testing-testo:^0.1" -W
```

`composer remove` first is mandatory: core declares
`conflict: {"rasuvaeff/property-testing": "*"}` because both packages ship
classes in the `Rasuvaeff\PropertyTesting` namespace, so a mixed install is
deliberately unsolvable rather than silently duplicated on the autoloader.
`-W` lets Composer raise `testo/testo` to the version the adapter requires
(`^0.10.39 || ^1.0`).

Everything that survives the move unchanged:

- every public FQCN — `Rasuvaeff\PropertyTesting\Gen`, `ArbitraryInterface`,
  `Shrinkable`, `Assume`, `Classify`, `Property`, the state machine and the
  public exceptions;
- the `<method>Generators()` and `<method>Examples()` conventions;
- `PROPERTY_SEED`, `PROPERTY_RUNS`, `PROPERTY_DB`, `PROPERTY_VERBOSE`;
- the counterexample message format;
- the regression corpus on disk — a corpus written by 2.8 is read by
  `-testo` (same `FORMAT_VERSION`, byte-compatible JSON);
- seed determinism (`SEQUENCE_EPOCH` is unchanged).

The full guide, including the custom-harness and PHPUnit paths, is
[MIGRATION.md in property-testing-core](https://github.com/rasuvaeff/property-testing-core/blob/master/MIGRATION.md).

## Documentation

The [documentation site](https://rasuvaeff.github.io/property-testing/)
documents this frozen 2.x line. Its guide chapters still describe the engine
accurately — the FQCNs and behaviour are the ones core and `-testo` ship — but
it is not updated with anything released after 2.8.1. The new family documents
itself in each package's `README.md`/`README.ru.md`, `llms.txt` and
`examples/`.

[llms.txt](llms.txt) in this repository remains a compact API reference for the
2.x line.

## Installation

Still installable and still works; new projects should not use it:

```bash
composer require --dev rasuvaeff/property-testing
```

## Examples

[examples/](examples/) still contains the runnable 2.x scripts (`basic.php`,
`property_test.php`, `generators.php`, `state_machine.php`); none of them needs
a server.

## Security

This package executes test methods via reflection (to read the `#[Property]`
attribute and invoke the generators method) and through Testo's pipeline. The
fallback Testo interceptor is `PropertyInterceptor`. It performs no I/O, SQL,
shell, or network operations itself. Random values are generated with PHP's
MT19937 engine seeded by the reported seed; do not rely on them for
cryptographic purposes.

Security fixes are the only changes this line still receives; they are
published as patch releases here and, where the same code exists in the new
family, in the corresponding package.

## Development

No PHP/Composer on the host. Run commands in Docker via the `composer:2` image:

```bash
docker run --rm -v "$PWD":/app -w /app composer:2 composer install
docker run --rm -v "$PWD":/app -w /app composer:2 composer build
```

Or with Make: `make install`, `make build`, `make cs-fix`, `make test`.

## License

[BSD-3-Clause](LICENSE.md)
