---
name: rasuvaeff-property-testing
description: >-
  Write property-based tests for the Testo framework with
  rasuvaeff/property-testing — the #[Property] attribute, the Gen generator
  facade, Assume::that(), Gen::draw(), Shrinkable, StateMachine::check().
  Use when writing, reviewing or debugging property tests (random inputs,
  shrinking, counterexamples, GaveUpException/GenerationExhausted failures)
  in a project that has this package installed.
---

# rasuvaeff/property-testing

Property-based testing plugin for Testo (PHP 8.3+): generate random inputs,
find a falsifying case, shrink it to a minimal counterexample.
Namespace `Rasuvaeff\PropertyTesting\`.

## Safety rules — verify these on every change

1. **Generators live in a `public static` method, never inline.** Attribute
   arguments are constant expressions, so arbitraries CANNOT be passed to
   `#[Property]`. Declare `public static function <testMethod>Generators(): array`
   returning `['paramName' => Gen::...]`. STRICTLY `public static` (`public`
   only if the body needs `$this`): the only call site is reflection, so
   Rector's `RemoveUnusedPrivateMethodRector` silently deletes private ones.
   Public is safe and never becomes a test (non-void return).

2. **The attribute is `#[Property(runs: N)]` — `#[Given]` does NOT exist.**
   Do not invent PHPUnit-style or other frameworks' attributes.

3. **Construct dependent values, do not discard.** Build them with
   `Gen::flatMap()` / `Gen::draw()` (e.g. `$max = $n + $slack`), not by
   rejecting via `Assume::that(...)` — broad rejection burns attempts into
   discards and fails with `GaveUpException` at `maxDiscards` (default
   `runs * 10`). `Gen::filter()` throws `GenerationExhausted` after 100 tries.

   ```php
   Gen::flatMap(Gen::nonEmptyArrayOf(Gen::int()), static fn(array $items) =>
       Gen::tuple(Gen::constant($items), Gen::intBetween(0, count($items) - 1)));
   ```

4. **`Gen::draw()` works only inside a property body** (throws a
   `RuntimeException` elsewhere). Counterexamples report draws as `draw#N`;
   after shrinking, assert what the body requires — a replayed draw is not
   re-validated against a changed range.

5. **Generated values are MT19937 pseudo-random — never use them for
   cryptography.** Pin `seed:` (or `PROPERTY_SEED`) only to reproduce a
   reported failure; leave it unset otherwise.

## Canonical usage

```php
use Rasuvaeff\PropertyTesting\Gen;
use Rasuvaeff\PropertyTesting\Property;
use Testo\Assert;

#[Property(runs: 200)]
public function sortedThenSortedIsIdempotent(array $xs): void
{
    $once = $this->sort($xs);

    Assert::same($this->sort($once), $once);
}

/** @return array<string, \Rasuvaeff\PropertyTesting\ArbitraryInterface> */
public static function sortedThenSortedIsIdempotentGenerators(): array
{
    return ['xs' => Gen::arrayOf(Gen::intBetween(-100, 100))];
}
```

| Need | Use |
|---|---|
| One dependent value | `Gen::flatMap()` |
| Several dependent / mid-body values | `Gen::draw()` inside the body |
| Rare precondition, construction impossible | `Assume::that()` (last resort) |
| Sequences of ops vs a model | `Gen::commands()` + `StateMachine::check()` |
| Prove the property is not vacuous | `Classify::label()` / `Classify::cover()` |

## Full API

The complete reference — every `Gen` combinator, `#[Property]` parameter
(`seed`, `examples`, `timeoutMs`, `budgetMs`, ...), `Shrinkable`, stateful
testing — ships with the package: read
`vendor/rasuvaeff/property-testing/llms.txt` before guessing a method name.
