---
title: API overview
description: "Every @api-tagged type in property-testing's src/, taken from a live reflection pass — Property, Gen, Assume, ArbitraryInterface, Shrinkable, CounterExample, Classify."
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
| [`Property`](/api/classes/Property) | The `#[Property]` attribute — `runs`, `seed`, `generators`, `maxShrinks`, `examples`, `maxDiscards`, `timeoutMs`, `budgetMs`. |
| [`Gen`](/api/classes/Gen) | Static factory for every built-in generator — see the [generators overview](/generators/index). |
| [`Assume`](/api/classes/Assume) | `Assume::that(bool)` — discard the current attempt. |
| [`ArbitraryInterface`](/api/classes/ArbitraryInterface) | Implement this to write a [custom generator](/generators/custom-arbitrary). |
| [`Shrinkable`](/api/classes/Shrinkable) | Value + lazy shrink tree — `leaf()`, `of()`, `map()`. See [Shrinking](/shrinking). |
| [`CounterExample`](/api/classes/CounterExample) | The falsified run's data — `toArray()`, `toJson()`, `toExamplesCode()`. |
| [`Classify`](/api/classes/Classify) | `label()`, `when()`, `cover()` — see [Distribution](/distribution). |

## State machine testing

| Type | Role |
|---|---|
| [`Command`](/api/classes/Command) | Interface: `preCondition`/`nextState`/`run`/`postCondition` + `\Stringable`. |
| [`CommandSequence`](/api/classes/CommandSequence) | The generated, shrinkable sequence of commands. |
| [`StateMachine`](/api/classes/StateMachine) | `StateMachine::check()` — runs a sequence against a fresh system. |

See [State machine: concepts](/state-machine/concepts).

## Failures

Every exception this package throws, with its actual `readonly` fields and
getters (not just a name-drop): **[Exceptions reference](/api/exceptions)**.

## Public, but not where you start

Two `@api` members exist for narrower, more advanced use than "write a
`#[Property]` test":

- [`Random::__construct(int $seed)`](/api/classes/Random) —
  build a `Random` by hand to exercise a custom `ArbitraryInterface` in
  isolation, outside of a running property. Inside a property, `Random` is
  always injected by the runner.
- [`Classify::beginRun()` / `flushRun()` / `flushRequirements()`](/api/classes/Classify) —
  the runner-lifecycle hooks `PropertyInterceptor` calls to reset and drain
  the per-run distribution buffer. Public because the interceptor lives in a
  different namespace (`Internal\`), not because test code is expected to
  call them.

`AssumptionSkipped` is deliberately **absent** from this map — it's public
but not `@api`; see the note at the bottom of the
[exceptions reference](/api/exceptions#not-part-of-the-contract).

<small>This page and [Exceptions](/api/exceptions) are checked
against a live reflection snapshot of <code>src/</code>, not hand-maintained
from memory — see <a href="https://github.com/rasuvaeff/property-testing/issues/29"
target="_blank" rel="noopener">issue #29</a> for the gaps this closed.</small>
