---
layout: home
title: property-testing
hero:
  name: property-testing
  text: Generate hundreds of inputs. Shrink the one that breaks it.
  tagline: Property-based testing for PHP 8.3+, built as a plugin for Testo.
  actions:
    - theme: brand
      text: What is property-testing?
      link: /en/intro/what-is-property-testing
    - theme: alt
      text: Getting started
      link: /en/intro/getting-started
    - theme: alt
      text: View on GitHub
      link: https://github.com/rasuvaeff/property-testing
features:
  - title: Integrated shrinking
    details: generate() returns value + lazy shrink tree together — transformed generators (map, flatMap) shrink correctly for free.
    link: /en/shrinking
  - title: Dependent values
    details: Gen::flatMap() and in-body Gen::draw() build valid inputs instead of discarding invalid ones.
    link: /en/generators/dependent
  - title: Regression corpus
    details: PROPERTY_DB replays every past failure first, so a fixed bug can't silently come back.
    link: /en/regression-corpus
  - title: Deadlines, not just assertions
    details: timeoutMs and budgetMs turn pathological inputs (catastrophic regex, deep recursion) into reported failures.
    link: /en/controlling-runs/deadlines
  - title: Stateful / model-based testing
    details: Generate whole sequences of Commands, run them against a model, shrink the sequence to the shortest failing one.
    link: /en/state-machine/concepts
  - title: A Testo plugin, not a runner
    details: "#[Property] self-registers with Testo's interceptor discovery — no separate CLI, no new framework."
    link: https://php-testo.github.io/
---

<div class="vp-doc" style="max-width: 960px; margin: 3rem auto 0; padding: 0 24px;">

## See it fail, then see it shrink

<div class="terminal-sample">
<pre><code>Property falsified after 246 successful run(s); seed=7382910
  Original: maxAttempts=17, baseSeconds=91, cap=847, attempts=23
  Shrunk:   maxAttempts=1, baseSeconds=848, cap=847, attempts=1 (12 shrink step(s), 41 trial(s))
  Changed:  maxAttempts=17 -&gt; 1, baseSeconds=91 -&gt; 848, attempts=23 -&gt; 1</code></pre>
</div>

Four generated arguments went in; the `Changed:` line tells you only three of
them actually drive the failure — the shrinker found that by searching, you
didn't have to step through a debugger to see it.

## Four ways to see it in code

:::code-group

```php [basic.php]
// The three pieces in isolation, no Testo runner involved.
$ints = Gen::intBetween(0, 1000);

$failing = null;
for ($run = 0; $run < 100; ++$run) {
    $shrinkable = $ints->generate($random);

    if ($shrinkable->value % 2 !== 0) {
        $failing = $shrinkable;
        break;
    }
}
// -> shrink toward the simplest odd int in range
```

```php [property_test.php]
#[Test]
final class ListReversalProperties
{
    #[Property(runs: 200)]
    public function reversingTwiceRestoresTheList(array $xs): void
    {
        Assert::same(array_reverse(array_reverse($xs)), $xs);
    }

    /** @return array<string, ArbitraryInterface> */
    public static function reversingTwiceRestoresTheListGenerators(): array
    {
        return ['xs' => Gen::arrayOf(Gen::intBetween(-100, 100))];
    }
}
```

```php [generators.php]
// Sample a generator directly — a quick way to eyeball what it produces.
Gen::sample(Gen::intBetween(1, 6), count: 5, seed: 42);
// [3, 1, 6, 6, 2]

Gen::sampleShrinks(Gen::intBetween(0, 100), seed: 1);
// ['value' => 87, 'shrinks' => [0, 44, 66, 77, 82, 85, 86]]
```

```php [state_machine.php]
#[Property(runs: 200)]
public function stackBehavesLikeItsModel(CommandSequence $sequence): void
{
    StateMachine::check($sequence, static fn(): ExampleStack => new ExampleStack());
}

/** @return array<string, ArbitraryInterface> */
public static function stackBehavesLikeItsModelGenerators(): array
{
    return ['sequence' => Gen::commands([], [
        Gen::map(Gen::intBetween(0, 99), static fn(int $v) => new Push($v)),
        Gen::constant(new Pop()),
    ])];
}
```

:::

Full, runnable versions of all four live in
[`examples/`](https://github.com/rasuvaeff/property-testing/blob/master/examples) —
see the [Examples](/en/examples) page for what each one shows.

</div>

<style>
.terminal-sample {
  background: #0f1c18;
  color: #d7f3e4;
  border-radius: 8px;
  padding: 1rem 1.2rem;
  overflow-x: auto;
  font-size: 0.85rem;
  line-height: 1.6;
}
.terminal-sample pre { margin: 0; }
.terminal-sample code { color: inherit; background: none; }
</style>
