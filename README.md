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

Since 2.0 shrinking is **integrated**: `generate()` returns a
[`Shrinkable`](src/Shrinkable.php) — the value plus a lazy tree of smaller
candidates — so transformed generators (`Gen::map()`, `Gen::flatMap()`) shrink
through their source domain. Upgrading from 1.x? See [UPGRADE.md](UPGRADE.md).

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

Declare generators (and examples) methods `public static` — or `public` if the
body needs `$this`. Their only call site is this package's reflection, so
static analysis sees them as unused: Rector's dead-code set deletes private
ones (`RemoveUnusedPrivateMethodRector`). Public methods are safe, and Testo
never treats a non-void-returning method as a test.

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
| `Gen::stringFrom($alphabet, $min, $max)` | `CharsetStringArbitrary`, characters from a fixed alphabet (multibyte OK) | toward `''`, then by length, then each character toward the first alphabet character |
| `Gen::bytes($min, $max)` | `BytesArbitrary`, raw byte strings (bytes 0..255) | toward `''`, then by length, then each byte toward `"\x00"` |
| `Gen::arrayOf($element, $min, $max)` | `ArrayArbitrary`, lists of `$element`, size 0..100 by default | toward `[]`, then by length, then each element |
| `Gen::nonEmptyArrayOf($element, $max)` | `ArrayArbitrary`, non-empty lists | by length (never below 1), then each element |
| `Gen::uniqueArrayOf($element, $min, $max)` | `UniqueArrayArbitrary`, lists of pairwise-distinct elements | like `arrayOf`, but element candidates colliding with another element are skipped |
| `Gen::dictOf($key, $value, $min, $max)` | `DictionaryArbitrary`, maps with keys from `$key` (int/string) and values from `$value`, size 0..100 by default | toward `[]`, then by size, then each value (keys fixed) |
| `Gen::record($shape)` | `RecordArbitrary`, fixed-shape map `['field' => $arb, ...]` | each field via its arbitrary, key set fixed |
| `Gen::elements($array)` | `OneOfArbitrary`, one value from an array (array form of `oneOf`) | toward earlier-listed distinct values |
| `Gen::enum(SomeEnum::class)` | `OneOfArbitrary` over the enum's cases | toward earlier-declared cases (declare simpler cases first) |
| `Gen::constant($value)` | `ConstantArbitrary`, always `$value` | does not shrink |
| `Gen::char()` | `StringArbitrary`, a single printable ASCII character | toward `a` |
| `Gen::uuid()` | `UuidArbitrary`, RFC 4122 v4 UUID strings | does not shrink |
| `Gen::datetime($min, $max)` | `DateTimeArbitrary`, UTC `DateTimeImmutable`, timestamp in `[$min, $max]` | toward the Unix epoch, clamped |
| `Gen::floatSpecial()` | `OneOfArbitrary` over `NAN`, `±INF`, `-0.0` and the float representation edges | toward earlier-listed specials |
| `Gen::intRange($min, $max)` | `FlatMappedArbitrary`, ordered pairs `[lo, hi]` with `lo <= hi` | both bounds shrink, order always holds |
| `Gen::recursive($leaf, $wrap, $maxDepth)` | bounded recursive structures: `$wrap` lifts the previous level's arbitrary | within the branch that generated the value |
| `Gen::oneOf(...$values)` | `OneOfArbitrary`, one of the given values | toward earlier-listed distinct values (put simpler values first) |
| `Gen::nullable($inner)` | `NullableArbitrary`, `null` or an `$inner` value | prefers `null`, then the inner tree |
| `Gen::map($inner, $fn)` | `MappedArbitrary`, `$inner` transformed by `$fn` | through the inner tree, re-applying `$fn` |
| `Gen::flatMap($inner, $fn)` | `FlatMappedArbitrary`, dependent generator returned by `$fn($innerValue)` | source value first (dependent value regenerated), then the dependent tree |
| `Gen::filter($inner, $predicate)` | `FilteredArbitrary`, `$inner` values satisfying `$predicate` | inner tree, pruning candidates that fail the predicate |
| `Gen::tuple(...$elements)` | `TupleArbitrary`, fixed-arity tuple, one value per element | each position via its element, arity fixed |
| `Gen::frequency($pairs)` | `FrequencyArbitrary`, weighted choice over `[weight, arbitrary]` pairs | within the branch that generated the value |
| `Gen::ipv4()` | IPv4 dotted-quad strings | each octet toward `0` |
| `Gen::email()` | `local@label.tld` addresses | toward the shortest local/label and first TLD |
| `Gen::url()` | `http(s)://host.tld[/path]` URLs | toward `http://a.com` |
| `Gen::json($maxDepth)` | a JSON-encodable value (null/bool/int/float/string/list/object) | within the generated structure |
| `Gen::jsonString($maxDepth)` | the `json_encode` text of `Gen::json()` | through the value's tree |
| `Gen::regex($pattern)` / `Gen::stringMatching($pattern)` | strings matching a regex subset (compiled to combinators) | shorter/simpler matches (via the compiled trees) |

Numeric generators (`int*`, `float*`) are **boundary-biased**: roughly one draw in
five returns an in-range edge value (`0`, `±1`, `min`, `max` for ints; `0.0` or
`min` for floats), where bugs cluster, instead of a uniform one. Shrinking is
unaffected.

### Dependent generators (`flatMap`)

When one input's domain depends on another — a list plus a valid index into it,
a size plus a payload of that size — `Gen::flatMap()` feeds each generated value
into a closure that returns the arbitrary for the final value. Unlike an
`Assume::that()` guard, no runs are discarded, and both levels shrink: the
source value shrinks (the dependent value is regenerated deterministically from
the run's seed), then the dependent value shrinks with the source held fixed.

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

### In-body draws (`Gen::draw`)

When several dependent values make nested `flatMap` awkward, draw them inside
the property body with `Gen::draw()`. The domain may depend on anything already
in scope — parameters, previous draws, intermediate results:

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

Drawn values shrink together with the parameters. The runner records every
draw on a replay tape; when the property fails, it shrinks each recorded draw
through its own tree and re-runs the body with the tape replayed by position.
A shrunk parameter can change the body's control flow: draws past the tape's
end are generated anew, and draws the smaller run no longer reaches are
dropped. Counterexamples report draws as `draw#1`, `draw#2`, ... next to the
named parameters (and `PROPERTY_VERBOSE` logs them per run).

Two things to know:

- A replayed draw is served by position and is **not** re-validated against
  the (possibly narrower) arbitrary of the new control flow — the same model
  as fast-check's `gen()`. Assert what the body actually requires rather than
  relying on the draw's range after shrinking.
- Because the tape can regrow during shrinking, the finite-tree termination
  argument no longer applies on its own; with draws present, accepted shrink
  steps are capped (1000 by default, `maxShrinks` still wins when set).

`Gen::draw()` is only valid while the runner executes a property body;
anywhere else it throws. Prefer `flatMap` for a single dependent value — it
keeps the whole domain visible in the generators method.

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
returns a [`Shrinkable`](src/Shrinkable.php) — the drawn value plus a lazy tree
of smaller candidates, most aggressive first, each carrying its own subtree.
Draw randomness only through the injected `Random` (`int()`, `float()`,
`bytes()`) so seeded runs stay reproducible.

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

A custom arbitrary is used like any built-in: return it from the generators
method keyed by parameter name. `Shrinkable::leaf($value)` builds a terminal
node (no candidates); `Shrinkable::of($value, $closure)` attaches lazily
computed candidates; `Shrinkable::map($fn)` transforms a whole tree. Keep every
branch of the tree finite and never yield a candidate equal to its parent —
that is what guarantees shrinking terminates.

### Environment overrides

Two environment variables tune runs without touching the attributes — useful in
CI:

| Variable | Effect |
|---|---|
| `PROPERTY_RUNS` | Positive integer that overrides every property's run count (dial runs up in CI). |
| `PROPERTY_SEED` | Integer seed used for any property whose attribute omits `seed` (replay a whole suite). An explicit attribute `seed` still wins. |
| `PROPERTY_VERBOSE` | Any value except `''`/`0` logs every run's generated arguments — see exactly what a replayed seed feeds the property. |
| `PROPERTY_DB` | Directory path enabling regression replay (below). Unset means the feature is off and nothing is written. |

### Replaying the last failure

Set `PROPERTY_DB` to a directory and a falsified property records the seed that
failed. On the next run that seed is re-run **first** (unless the attribute pins
its own `seed`): a still-failing seed is reported immediately for fast feedback,
and a seed that no longer fails is forgotten. Only the seed is stored — never the
generated values, which may be objects or closures — so re-running the seed
reproduces the same draw. Storage is one small file per property
(`<sha1(id)>.seed`); add the directory to `.gitignore`.

### Explicit examples

Fixed inputs pin a found bug as a permanent case that runs on every invocation,
alongside the random ones. Declare a `<testMethod>Examples` method (or name one
via `#[Property(examples: 'method')]`) returning positional argument tuples; each
runs **before** the random inputs and is reported verbatim (not shrunk — it is
already the minimal case you pinned) via `ExampleViolationException`.

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

### Checking the distribution

A property can pass vacuously if its generators never reach the interesting
inputs. `Classify` records labels per run; after a fully passing property the
runner prints the share of runs that hit each label.

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

A label recorded several times within one run still counts once for that run.

### Enforcing the distribution

`Classify::cover()` upgrades the printed hint to a hard requirement: the label
must occur in at least the given percentage of passing runs, or the property
**fails** with a `CoverageViolationException` — even though every run passed.
Use it to make vacuous passes impossible in CI.

```php
#[Property(runs: 500)]
public function holds(int $n): void
{
    Classify::cover($n % 2 === 0, 'even', 30.0); // fail if < 30% of runs are even
    // ... assertions ...
}
```

Discarded runs (`Assume::that()`) are excluded from the denominator. A property
whose runs are all discarded fails its coverage requirements outright.

### Sampling a generator

`Gen::sample()` eagerly generates values from any arbitrary for a fixed seed — a
quick way to eyeball what a generator produces (it returns values, not an
arbitrary).

```php
Gen::sample(Gen::intBetween(1, 6), count: 5, seed: 42); // [3, 1, 6, 6, 2]
```

`Gen::sampleShrinks()` does the same for the shrink tree: it generates one
value and lists its first direct shrink candidates — the fastest way to check
that a custom arbitrary shrinks the way you intended.

```php
Gen::sampleShrinks(Gen::intBetween(0, 100), seed: 1);
// ['value' => 87, 'shrinks' => [0, 44, 66, 77, 82, 85, 86]]
```

### Recipes

Dependent values without discards — build, don't filter:

```php
// A size and a payload of exactly that size.
Gen::flatMap(Gen::intBetween(1, 32), static fn(int $size): ArbitraryInterface
    => Gen::tuple(Gen::constant($size), Gen::bytes($size, $size)));

// An ordered interval: Gen::intRange(0, 1440) yields [lo, hi] with lo <= hi.

// Domain strings from an alphabet instead of filtering Unicode.
Gen::stringFrom('abcdefghijklmnopqrstuvwxyz0123456789-', 1, 63); // hostname label
```

Bounded recursive data:

```php
use Rasuvaeff\PropertyTesting\Arbitrary\ArrayArbitrary;

// JSON-ish scalars nested in small arrays, at most 3 levels deep.
Gen::recursive(
    Gen::oneOf(null, true, false, 0, 1, 'a'),
    static fn(ArbitraryInterface $inner): ArbitraryInterface => new ArrayArbitrary($inner, 0, 3),
    maxDepth: 3,
);
```

Keep the branch fan-out small (bounded array sizes): breadth multiplies at
every level of nesting.

### Stateful / model-based testing

Some bugs only surface across a *sequence* of operations — a counter that
overflows after N increments, a cache that returns stale data, a stack that
loses ordering. Model-based testing generates random sequences of commands,
runs each against the real system while mirroring it in a simplified model, and
on failure **shrinks the sequence** to the shortest one that still breaks.

Implement [`Command`](src/StateMachine/Command.php) — four pure-ish
responsibilities plus a label:

| Method | Purpose |
|---|---|
| `preCondition(mixed $model): bool` | May this command run in the current model state? Gates generation and, on replay, whether the command runs or is skipped. |
| `nextState(mixed $model): mixed` | The model's expected next state (pure; returns a new model, never mutates). |
| `run(mixed $model, mixed $system): mixed` | Execute against the system under test; return the observed result. |
| `postCondition(mixed $model, mixed $result): bool` | Check the result against the pre-state model. Return `false` (or throw) to falsify. |
| `__toString(): string` | Label used in the counterexample trace. |

`Gen::commands($initialModel, $commandGenerators)` builds valid sequences (each
step appends a command whose precondition holds, then advances the model), and
`StateMachine::check()` drives the generated sequence against a fresh system:

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

Shrinking removes whole blocks of commands (down to a single one, so a failing
step in the middle is isolated) and then simplifies each command's parameters
through its own tree. Because the runner re-checks each precondition and skips
any a dropped step invalidated, every shrunk sequence stays sound. The
counterexample renders as a readable trace, and a failed postcondition throws a
[`PostconditionViolation`](src/StateMachine/PostconditionViolation.php) naming
the step:

```
Property falsified after 7 successful run(s); seed=42
  Original: sequence=[Push(3), Pop(), Push(5), Push(1), Pop(), Pop()]
  Shrunk:   sequence=[Push(0), Push(1), Pop()] (9 shrink step(s))
  Failure:  Postcondition failed at step 3 for command Pop(); sequence: [Push(0), Push(1), Pop()]
```

See [`examples/state_machine.php`](examples/state_machine.php) for the full stack
example.

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
| `basic.php` | a property that holds, one that is falsified, and tree-based shrinking | No |
| `property_test.php` | canonical `#[Property]` usage as a real Testo test case | No |
| `generators.php` | `sample`, boundary bias, `uuid`, `datetime`, `dictOf`, `record`, `flatMap` | No |
| `state_machine.php` | stateful / model-based testing: `Command`, `Gen::commands()`, `StateMachine::check()` | No |

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
