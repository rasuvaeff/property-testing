---
title: "Cookbook: your first property"
description: "A worked walkthrough of property_test.php: from an empty Testo test class to a falsified property and back to green."
---

# Cookbook: your first property

Walking through [`property_test.php`](https://github.com/rasuvaeff/property-testing/blob/master/examples/property_test.php)
from an empty test class to a falsified property and back to green.

## 1. Install and write a Testo test case

```
composer require --dev rasuvaeff/property-testing
```

A property is a normal `#[Test]` class method, marked `#[Property]` instead
of asserting on hardcoded values:

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

Two things make this a property rather than an example-based test:

- The method takes a parameter (`array $xs`) instead of hardcoding one input.
- A sibling method named `<methodName>Generators` (or named explicitly via
  `#[Property(generators: '...')]`) returns one `ArbitraryInterface` per
  parameter, keyed by parameter name. See [Generators](/generators/index)
  — attribute arguments must be constant expressions, so the generator can't
  live inline in `#[Property(...)]`.

Run it:

```bash
docker run --rm -v "$PWD":/app -w /app composer:2 vendor/bin/testo
```

`runs: 200` means 200 *successful* checks — discarded attempts (see
[Assume vs filter](/controlling-runs/assume-vs-filter)) don't count toward
that total.

## 2. Write one that fails, on purpose

Swap the assertion for a wrong invariant to see a falsified property end to
end:

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

Output looks like:

```
Property falsified after 3 successful run(s); seed=918273645
  Original: n=847
  Shrunk:   n=1 (4 shrink step(s), 9 trial(s))
```

Read it right to left:

- **`seed`** — feed it back via `#[Property(seed: 918273645)]` to replay this
  exact run; see [Reproducing with a seed](/cookbook/reproducing-with-seed).
- **`Original`** — the first input that failed, unmodified.
- **`Shrunk`** — the smallest input the shrinker could find that still fails,
  reached by a greedy descent through the value's shrink tree. `1` is in fact
  the minimal odd int the shrinker can reach shrinking toward `0`. See
  [Shrinking](/shrinking) for how the descent works and why it terminates.
- **`shrink step(s)` / `trial(s)`** — accepted descents vs. every candidate
  tried (accepted + rejected).

## 3. Make it pass again

Fix the assertion (or the code under test) and re-run. A property that holds
prints nothing extra — Testo reports it like any other passing test. There is
no separate "property mode" report to check; the failure output above is the
only extra signal this package adds over a normal assertion failure.

## Where to go next

- [Concepts](/intro/concepts) for the vocabulary (arbitrary, shrinking,
  counterexample) this page used without defining.
- [Generators overview](/generators/index) for the full `Gen::*` catalog.
- [Reproducing with a seed](/cookbook/reproducing-with-seed) once you've
  hit your first real failure and need to debug it, not just read the
  one-line summary.
