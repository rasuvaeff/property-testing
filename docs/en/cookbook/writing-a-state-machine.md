---
title: "Cookbook: writing a state machine test"
---

# Cookbook: writing a state machine test

An end-to-end walkthrough of [`examples/state_machine.php`](https://github.com/rasuvaeff/property-testing/blob/master/examples/state_machine.php):
testing a stack by generating random sequences of pushes and pops and
comparing the real stack against a plain-array model.

See [State machine: concepts](/en/state-machine/concepts) for the `Command`
interface reference and [State machine: shrinking](/en/state-machine/shrinking)
for how a failing sequence gets minimized — this page is the practical build
order.

## 1. The system under test

Nothing property-testing-specific yet — an ordinary class:

```php
final class ExampleStack
{
    /** @var list<int> */
    private array $items = [];

    public function push(int $value): void
    {
        $this->items[] = $value;
    }

    public function pop(): int
    {
        $value = array_pop($this->items);
        if ($value === null) {
            throw new \UnderflowException('pop from empty stack');
        }
        return $value;
    }

    public function size(): int
    {
        return count($this->items);
    }
}
```

## 2. Pick a model

The model is the simplest data structure that predicts what the system
*should* do. For a stack, that's a plain `list<int>` — `Gen::commands()`'s
initial model:

```php
Gen::commands([], [...]) // initialModel = []
```

## 3. One `Command` class per operation

Each command implements four methods plus a label. `Push` is unconditionally
applicable:

```php
final readonly class Push implements Command
{
    public function __construct(private int $value) {}

    public function preCondition(mixed $model): bool
    {
        return true;
    }

    public function nextState(mixed $model): mixed
    {
        \assert(is_array($model));
        return [...$model, $this->value];
    }

    public function run(mixed $model, mixed $system): mixed
    {
        \assert($system instanceof ExampleStack);
        $system->push($this->value);
        return $system->size();
    }

    public function postCondition(mixed $model, mixed $result): bool
    {
        \assert(is_array($model));
        return $result === count($model) + 1;
    }

    public function __toString(): string
    {
        return 'Push(' . $this->value . ')';
    }
}
```

`Pop` is only applicable when the model is non-empty — this is what keeps
generated sequences valid by construction (`Gen::commands()` never appends a
command whose `preCondition` fails against the running model):

```php
final readonly class Pop implements Command
{
    public function preCondition(mixed $model): bool
    {
        \assert(is_array($model));
        return $model !== [];
    }

    public function nextState(mixed $model): mixed
    {
        \assert(is_array($model));
        return array_slice($model, 0, -1);
    }

    public function run(mixed $model, mixed $system): mixed
    {
        \assert($system instanceof ExampleStack);
        return $system->pop();
    }

    public function postCondition(mixed $model, mixed $result): bool
    {
        \assert(is_array($model) && $model !== []);
        return $result === $model[array_key_last($model)];
    }

    public function __toString(): string
    {
        return 'Pop()';
    }
}
```

Notice what each method checks: `preCondition` gates *generation and replay*
(can this command run right now), `nextState` is a pure prediction of the
model after the command runs, `run` is the only method that touches the real
system, and `postCondition` compares the *real* result against what the
*pre-state* model predicted — not the post-state model, since the point is to
catch the system disagreeing with the prediction.

## 4. Wire it into a property

```php
use Testo\Test;

#[Test]
final class StackStateMachineProperties
{
    #[Property(runs: 200)]
    public function stackBehavesLikeItsModel(CommandSequence $sequence): void
    {
        StateMachine::check($sequence, static fn(): ExampleStack => new ExampleStack());
    }

    /** @return array<string, ArbitraryInterface> */
    public static function stackBehavesLikeItsModelGenerators(): array
    {
        return [
            'sequence' => Gen::commands([], [
                Gen::map(Gen::intBetween(0, 99), static fn(mixed $v): Push => new Push((int) $v)),
                Gen::constant(new Pop()),
            ]),
        ];
    }
}
```

Two things worth noticing:

- The generators array is a **list of command generators**, not a list of
  commands — `Gen::commands()` draws one at each step and only appends it if
  its `preCondition` currently holds. `Push` uses `Gen::map()` over an int
  generator (so pushed values vary); `Pop` has no parameters, so
  `Gen::constant(new Pop())` is enough.
- The `$system` argument to `StateMachine::check()` is a **factory**
  (`Closure(): mixed`), not an instance — it must build a *fresh* system for
  every run, since the same property runs hundreds of times.

## 5. Run it, and break it on purpose

```bash
docker run --rm -v "$PWD":/app -w /app composer:2 vendor/bin/testo
```

To see a real failure, swap `Push`'s `postCondition` to something wrong
(e.g. `$result === count($model)`, off by one) and re-run — you'll get a
`PostconditionViolation` naming the exact step, with the sequence already
shrunk down toward the shortest `Push`/`Pop` combination that still
reproduces it. That's the payoff over single-shot properties: the bug in
this example (an off-by-one in a size assertion) only shows up *after* at
least one command already ran against the system, which a property with a
single input parameter has no way to express.
