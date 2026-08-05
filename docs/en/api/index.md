---
title: API overview
---

# API overview

This page maps every `@api`-tagged type in `src/` — the actual public
contract, taken from a reflection pass (`docs/scripts/reflect-api.php`), not
from what the README happens to mention. Every class name links to a
generated reference page (`docs/scripts/generate-api.mjs`) listing its
public properties and method signatures straight from that same reflection
pass.

## Where to start

| Type | Role |
|---|---|
| [`Property`](/en/api/classes/Property) | The `#[Property]` attribute — `runs`, `seed`, `generators`, `maxShrinks`, `examples`, `maxDiscards`, `timeoutMs`, `budgetMs`. |
| [`Gen`](/en/api/classes/Gen) | Static factory for every built-in generator — see the [generators overview](/en/generators/index). |
| [`Assume`](/en/api/classes/Assume) | `Assume::that(bool)` — discard the current attempt. |
| [`ArbitraryInterface`](/en/api/classes/ArbitraryInterface) | Implement this to write a [custom generator](/en/generators/custom-arbitrary). |
| [`Shrinkable`](/en/api/classes/Shrinkable) | Value + lazy shrink tree — `leaf()`, `of()`, `map()`. See [Shrinking](/en/shrinking). |
| [`CounterExample`](/en/api/classes/CounterExample) | The falsified run's data — `toArray()`, `toJson()`, `toExamplesCode()`. |
| [`Classify`](/en/api/classes/Classify) | `label()`, `when()`, `cover()` — see [Distribution](/en/distribution). |

## State machine testing

| Type | Role |
|---|---|
| [`Command`](/en/api/classes/Command) | Interface: `preCondition`/`nextState`/`run`/`postCondition` + `\Stringable`. |
| [`CommandSequence`](/en/api/classes/CommandSequence) | The generated, shrinkable sequence of commands. |
| [`StateMachine`](/en/api/classes/StateMachine) | `StateMachine::check()` — runs a sequence against a fresh system. |

See [State machine: concepts](/en/state-machine/concepts).

## Failures

Every exception this package throws, with its actual `readonly` fields and
getters (not just a name-drop): **[Exceptions reference](/en/api/exceptions)**.

## Public, but not where you start

Two `@api` members exist for narrower, more advanced use than "write a
`#[Property]` test":

- [`Random::__construct(int $seed)`](/en/api/classes/Random) —
  build a `Random` by hand to exercise a custom `ArbitraryInterface` in
  isolation, outside of a running property. Inside a property, `Random` is
  always injected by the runner.
- [`Classify::beginRun()` / `flushRun()` / `flushRequirements()`](/en/api/classes/Classify) —
  the runner-lifecycle hooks `PropertyInterceptor` calls to reset and drain
  the per-run distribution buffer. Public because the interceptor lives in a
  different namespace (`Internal\`), not because test code is expected to
  call them.

`AssumptionSkipped` is deliberately **absent** from this map — it's public
but not `@api`; see the note at the bottom of the
[exceptions reference](/en/api/exceptions#not-part-of-the-contract).

<small>This page and <a href="/en/api/exceptions">Exceptions</a> are checked
against a live reflection snapshot of <code>src/</code>, not hand-maintained
from memory — see <a href="https://github.com/rasuvaeff/property-testing/issues/29"
target="_blank" rel="noopener">issue #29</a> for the gaps this closed.</small>
