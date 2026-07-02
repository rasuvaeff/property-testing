# Upgrading

## 1.x → 2.0 — integrated shrinking

2.0 changes one thing: **shrinking moved from values to generation trees**.
`ArbitraryInterface::generate()` now returns a `Shrinkable` (the value plus a
lazy tree of smaller candidates) and `ArbitraryInterface::shrink(mixed)` is
gone. Everything else — `#[Property]`, `Gen` factories, `Assume`, `Classify`,
env overrides, the failure output — is unchanged.

### Who is affected

| You... | Impact |
|---|---|
| Only write `#[Property]` tests using `Gen` factories | **None.** Test code is source-compatible; recompile and go |
| Call `->generate($random)` directly (scripts, samples) | Value is now at `->generate($random)->value` |
| Call `->shrink($value)` directly | Removed. Walk `->generate($random)->shrinks()` instead |
| Implement `ArbitraryInterface` | Rewrite required (see below) |
| Rely on exact shrink sequences / seeds | Shrunk counterexamples may differ (usually smaller); seeds reproduce only within one major |

### Custom arbitraries

1.x shape:

```php
public function generate(Random $random): mixed;
public function shrink(mixed $value): iterable;
```

2.0 shape — one method returning a tree:

```php
use Rasuvaeff\PropertyTesting\Shrinkable;

#[\Override]
public function generate(Random $random): Shrinkable
{
    return $this->tree($this->draw($random));
}

private function tree(int $value): Shrinkable
{
    return Shrinkable::of($value, function () use ($value): \Generator {
        // yield what shrink($value) used to yield, wrapped recursively:
        foreach ($this->candidates($value) as $candidate) {
            yield $this->tree($candidate);
        }
    });
}
```

Rules:

- Build nodes with `Shrinkable::of($value, $lazyChildren)` and
  `Shrinkable::leaf($value)` for terminal values.
- Order candidates most aggressive first (zero/empty/identity first).
- Every branch must be finite and no candidate may equal its parent value —
  the runner's descent relies on it to terminate.
- Type guards inside `shrink()` (`if (!is_int($value)) return;`) are obsolete:
  the tree is built from the value you just generated.
- Wrapping/delegating arbitraries should pass inner `Shrinkable`s through
  (or transform them with `->map()`), not re-derive candidates from raw values.

### What integrated shrinking buys you

- `Gen::map($inner, $fn)` now shrinks: candidates come from the inner tree and
  `$fn` is re-applied (1.x reported the original counterexample unshrunk).
- `Gen::flatMap($inner, $fn)` (new): dependent generators without
  `Assume::that()` discards; both the source and the dependent value shrink.
- `Gen::frequency()` shrinks within the branch that generated the value instead
  of asking every branch.
- `Gen::oneOf()`/`Gen::elements()` shrink toward earlier-listed values, so the
  shrink index strictly decreases: list simpler values first.
- `IntArbitrary` shrinks by halving the distance to the in-range target — a
  binary search that lands on the exact boundary of monotone predicates.
