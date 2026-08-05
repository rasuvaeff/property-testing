---
layout: home
title: property-testing
hero:
  name: property-testing
  text: Сгенерируй сотни входов. Shrink'айте тот, что ломает.
  tagline: Property-based testing для PHP 8.3+, плагин для Testo.
  actions:
    - theme: brand
      text: Что такое property-testing?
      link: /ru/intro/what-is-property-testing
    - theme: alt
      text: Быстрый старт
      link: /ru/intro/getting-started
    - theme: alt
      text: Открыть на GitHub
      link: https://github.com/rasuvaeff/property-testing
features:
  - title: Integrated shrinking
    details: generate() возвращает значение + ленивое shrink-дерево вместе — трансформированные генераторы (map, flatMap) shrink'аются корректно бесплатно.
    link: /ru/shrinking
  - title: Зависимые значения
    details: Gen::flatMap() и in-body Gen::draw() строят валидные входы вместо отбрасывания невалидных.
    link: /ru/generators/dependent
  - title: Корпус регрессий
    details: PROPERTY_DB реплеит каждое прошлое падение первым, чтобы исправленный баг не мог незаметно вернуться.
    link: /ru/regression-corpus
  - title: Дедлайны, а не только assertion'ы
    details: timeoutMs и budgetMs превращают патологические входы (catastrophic regex, глубокая рекурсия) в сообщённые падения.
    link: /ru/controlling-runs/deadlines
  - title: Stateful / model-based тестирование
    details: Генерируй целые последовательности Command, прогоняй их против модели, shrink'уй последовательность до кратчайшей падающей.
    link: /ru/state-machine/concepts
  - title: Плагин Testo, а не раннер
    details: "#[Property] сам регистрируется через interceptor discovery Testo — без отдельного CLI, без нового фреймворка."
    link: https://php-testo.github.io/
---

<div class="vp-doc" style="max-width: 960px; margin: 3rem auto 0; padding: 0 24px;">

## Увидеть падение, потом увидеть shrink

<div class="terminal-sample">
<pre><code>Property falsified after 246 successful run(s); seed=7382910
  Original: maxAttempts=17, baseSeconds=91, cap=847, attempts=23
  Shrunk:   maxAttempts=1, baseSeconds=848, cap=847, attempts=1 (12 shrink step(s), 41 trial(s))
  Changed:  maxAttempts=17 -&gt; 1, baseSeconds=91 -&gt; 848, attempts=23 -&gt; 1</code></pre>
</div>

На вход пошли четыре сгенерированных аргумента; строка `Changed:` говорит,
что на падение реально влияют только три из них — shrinker нашёл это
поиском, вам не пришлось идти в дебаггер, чтобы это увидеть.

## Четыре способа увидеть это в коде

:::code-group

```php [basic.php]
// Три составляющие по отдельности, без раннера Testo.
$ints = Gen::intBetween(0, 1000);

$failing = null;
for ($run = 0; $run < 100; ++$run) {
    $shrinkable = $ints->generate($random);

    if ($shrinkable->value % 2 !== 0) {
        $failing = $shrinkable;
        break;
    }
}
// -> shrink к простейшему нечётному int в диапазоне
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
// Сэмплирование генератора напрямую — быстрый способ посмотреть, что он производит.
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

Полные, исполняемые версии всех четырёх — в
[`examples/`](https://github.com/rasuvaeff/property-testing/blob/master/examples);
что каждый из них показывает — на странице [Примеры](/ru/examples).

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
