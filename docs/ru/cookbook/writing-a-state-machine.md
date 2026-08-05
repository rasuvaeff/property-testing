---
title: "Рецепт: state machine тест"
---

# Рецепт: state machine тест

Полный разбор [`examples/state_machine.php`](https://github.com/rasuvaeff/property-testing/blob/master/examples/state_machine.php):
тестируем стек, генерируя случайные последовательности push/pop и сверяя
реальный стек с моделью — простым массивом.

См. [State machine: концепции](/ru/state-machine/concepts) для справки по
интерфейсу `Command` и [State machine: shrinking](/ru/state-machine/shrinking)
про то, как минимизируется падающая последовательность — эта страница про
практический порядок сборки.

## 1. Система под тестом

Пока ничего специфичного для property-testing — обычный класс:

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

## 2. Выбрать модель

Модель — простейшая структура данных, предсказывающая, что система
*должна* делать. Для стека это обычный `list<int>` — начальная модель
`Gen::commands()`:

```php
Gen::commands([], [...]) // initialModel = []
```

## 3. Один класс `Command` на операцию

Каждая команда реализует четыре метода плюс лейбл. `Push` применима
безусловно:

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

`Pop` применима только когда модель непуста — это то, что держит
сгенерированные последовательности валидными по построению
(`Gen::commands()` никогда не добавляет команду, чья `preCondition` не
проходит против текущей модели):

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

Обратите внимание, что проверяет каждый метод: `preCondition` гейтит
*генерацию и реплей* (может ли команда выполниться прямо сейчас),
`nextState` — чистое предсказание модели после выполнения команды, `run` —
единственный метод, трогающий реальную систему, а `postCondition` сравнивает
*реальный* результат с тем, что предсказала модель *до* выполнения — а не
модель после, поскольку суть в том, чтобы поймать расхождение системы с
предсказанием.

## 4. Подключить к property

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

Две детали, на которые стоит обратить внимание:

- Массив генераторов — это **список генераторов команд**, а не список
  команд: `Gen::commands()` тянет один на каждом шаге и добавляет его,
  только если его `preCondition` сейчас выполняется. `Push` использует
  `Gen::map()` поверх int-генератора (чтобы значения push варьировались);
  у `Pop` параметров нет, поэтому достаточно `Gen::constant(new Pop())`.
- Аргумент `$system` в `StateMachine::check()` — это **фабрика**
  (`Closure(): mixed`), а не экземпляр: она обязана строить *свежую* систему
  на каждый прогон, поскольку одно property выполняется сотни раз.

## 5. Запустить и намеренно сломать

```bash
docker run --rm -v "$PWD":/app -w /app composer:2 vendor/bin/testo
```

Чтобы увидеть реальное падение, подмените `postCondition` у `Push` на
что-то неверное (например, `$result === count($model)`, off-by-one) и
перезапустите — получите `PostconditionViolation`, называющий точный шаг,
с уже шринкнутой последовательностью до кратчайшей комбинации `Push`/`Pop`,
всё ещё воспроизводящей его. В этом выигрыш перед single-shot property: баг
в этом примере (off-by-one в проверке размера) проявляется только *после*
того, как против системы уже выполнилась хотя бы одна команда — property с
одним входным параметром никак не может это выразить.
