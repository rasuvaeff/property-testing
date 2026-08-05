---
title: What is property-testing
description: "Property-based testing generates hundreds of random inputs against a stated law, then shrinks the one that breaks it to a minimal, readable counterexample."
---

# What is property-testing

Most PHP tests are **example-based**: pick an input, assert an output.

```php
public function reversingAOneTwoThree(): void
{
    Assert::same([3, 2, 1], array_reverse([1, 2, 3]));
}
```

That proves the function does the right thing for `[1, 2, 3]`. It says
nothing about `[]`, `[0]`, a 10 000-element list, or a list containing
`-0.0`. Every additional case you think of is another test you have to write
— and the ones you didn't think of are exactly where bugs hide.

**Property-based testing** flips the direction. Instead of picking an input,
you state a law that must hold for *every* input in some domain:

```php
#[Property(runs: 200)]
public function reversingTwiceRestoresTheList(array $xs): void
{
    Assert::same(array_reverse(array_reverse($xs)), $xs);
}
```

The runner generates 200 random arrays — empty ones, huge ones, ones full of
duplicates, negative numbers, `PHP_INT_MAX` — and checks the law against each
one. If one of them fails, it doesn't just report that random input: it
**shrinks** it, searching a tree of smaller candidates for the simplest input
that still breaks the law, and reports that instead.

```
Property falsified after 246 successful run(s); seed=7382910
  Original: maxAttempts=17, baseSeconds=91, cap=847, attempts=23
  Shrunk:   maxAttempts=1, baseSeconds=848, cap=847, attempts=1 (12 shrink step(s), 41 trial(s))
  Changed:  maxAttempts=17 -> 1, baseSeconds=91 -> 848, attempts=23 -> 1
```

That's the whole idea: **generate broadly, then minimize precisely.** The
`Changed:` line above is the payoff — of four generated arguments, only three
actually drive the failure, and you know that without stepping through a
debugger.

## What this package adds on top

- A `#[Property]` attribute that plugs straight into a normal
  [Testo](https://php-testo.github.io/) test case — no separate runner, no
  new test framework to learn.
- Dozens of built-in generators (`Gen::int()`, `Gen::string()`,
  `Gen::arrayOf()`, …) plus the tools to build your own — see
  [Generators](/generators/index).
- **Integrated shrinking**: every generator already knows how to minimize its
  own values — see [Shrinking](/shrinking).
- A path beyond single inputs, into random *sequences* of operations against
  a stateful system — see [State machine testing](/state-machine/concepts).

## Next

[Getting started](/intro/getting-started) installs the package and walks
through writing the first property. [Concepts](/intro/concepts) is a
short glossary of the vocabulary used across this site (property, arbitrary,
shrinking, counterexample, seed) if you'd rather read definitions first.
