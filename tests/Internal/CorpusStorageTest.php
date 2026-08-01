<?php

declare(strict_types=1);

namespace Rasuvaeff\PropertyTesting\Tests\Internal;

use Rasuvaeff\PropertyTesting\CounterExample;
use Rasuvaeff\PropertyTesting\Internal\CorpusEntry;
use Rasuvaeff\PropertyTesting\Internal\CorpusStorage;
use Rasuvaeff\PropertyTesting\Tests\Support\Priority;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Lifecycle\AfterTest;
use Testo\Lifecycle\BeforeTest;
use Testo\Test;

#[Test]
#[Covers(CorpusStorage::class)]
final class CorpusStorageTest
{
    private const string ID = 'X::y';

    private string $dir = '';

    public function recallIsEmptyWithoutARecord(): void
    {
        Assert::same($this->storage()->recall(self::ID, ['x']), []);
    }

    public function remembersTheMinimisedInputAsAValuesEntry(): void
    {
        $storage = $this->storage();
        $storage->remember(self::ID, $this->counterExample(['x' => 51], 4242), ['x']);

        $entries = $storage->recall(self::ID, ['x']);

        Assert::same(count($entries), 1);
        Assert::true($entries[0]->isValues());
        Assert::same($entries[0]->arguments, ['x' => 51]);
        Assert::same($entries[0]->seed, 4242);
    }

    public function remembersRichValuesLosslessly(): void
    {
        $arguments = [
            'i' => -1,
            's' => "\xff\x00raw",
            'f' => -0.0,
            'a' => ['k' => [1, null, true], 3 => 'gap'],
            'e' => Priority::High,
        ];
        $storage = $this->storage();
        $storage->remember(self::ID, $this->counterExample($arguments, 7), array_keys($arguments));

        $entries = $storage->recall(self::ID, array_keys($arguments));

        Assert::same(count($entries), 1);
        Assert::same($entries[0]->arguments, $arguments);
    }

    public function fallsBackToASeedEntryWhenAValueIsNotRepresentable(): void
    {
        $storage = $this->storage();
        $storage->remember(self::ID, $this->counterExample(['d' => new \DateTimeImmutable('2026-01-01')], 99), ['d']);

        $entries = $storage->recall(self::ID, ['d']);

        Assert::same(count($entries), 1);
        Assert::false($entries[0]->isValues());
        Assert::same($entries[0]->seed, 99);
    }

    /**
     * In-body `Gen::draw()` results ride along in `shrunkArguments` as `draw#N`
     * pseudo-arguments. Replaying only the named parameters would let the body
     * draw fresh values, so such a counterexample must be stored as a seed.
     */
    public function fallsBackToASeedEntryWhenDrawPseudoArgumentsArePresent(): void
    {
        $storage = $this->storage();
        $storage->remember(self::ID, $this->counterExample(['x' => 1, 'draw#1' => 5], 33), ['x']);

        $entries = $storage->recall(self::ID, ['x']);

        Assert::same(count($entries), 1);
        Assert::false($entries[0]->isValues());
        Assert::same($entries[0]->seed, 33);
    }

    public function accumulatesSeveralDistinctFailures(): void
    {
        $storage = $this->storage();
        $storage->remember(self::ID, $this->counterExample(['x' => 51], 1), ['x']);
        $storage->remember(self::ID, $this->counterExample(['x' => 77], 2), ['x']);

        $entries = $storage->recall(self::ID, ['x']);

        Assert::same(count($entries), 2);
        // Newest first.
        Assert::same($entries[0]->arguments, ['x' => 77]);
        Assert::same($entries[1]->arguments, ['x' => 51]);
    }

    public function recordingTheSameInputTwiceKeepsOneEntry(): void
    {
        $storage = $this->storage();
        $storage->remember(self::ID, $this->counterExample(['x' => 51], 1), ['x']);
        $storage->remember(self::ID, $this->counterExample(['x' => 51], 2), ['x']);

        $entries = $storage->recall(self::ID, ['x']);

        Assert::same(count($entries), 1);
        // The newer recording wins, so the reported seed is the latest one.
        Assert::same($entries[0]->seed, 2);
    }

    public function valuesEntriesComeBackBeforeSeedEntries(): void
    {
        $storage = $this->storage();
        $storage->remember(self::ID, $this->counterExample(['x' => new \stdClass()], 1), ['x']);
        $storage->remember(self::ID, $this->counterExample(['x' => 51], 2), ['x']);

        $entries = $storage->recall(self::ID, ['x']);

        Assert::same(count($entries), 2);
        // A values entry costs one run, a seed entry a whole random phase.
        Assert::true($entries[0]->isValues());
        Assert::false($entries[1]->isValues());
    }

    public function capsValuesEntriesAndEvictsTheOldest(): void
    {
        $storage = $this->storage();

        for ($x = 1; $x <= 11; ++$x) {
            $storage->remember(self::ID, $this->counterExample(['x' => $x], $x), ['x']);
        }

        $entries = $storage->recall(self::ID, ['x']);

        Assert::same(count($entries), 8);
        Assert::same($entries[0]->arguments, ['x' => 11]);
        Assert::same($entries[7]->arguments, ['x' => 4]);
    }

    public function capsSeedEntriesMoreTightlyThanValuesEntries(): void
    {
        $storage = $this->storage();

        for ($seed = 1; $seed <= 5; ++$seed) {
            $storage->remember(self::ID, $this->counterExample(['x' => new \stdClass()], $seed), ['x']);
        }

        $entries = $storage->recall(self::ID, ['x']);

        Assert::same(count($entries), 2);
        Assert::same($entries[0]->seed, 5);
        Assert::same($entries[1]->seed, 4);
    }

    /**
     * The cap skips over-quota entries, it does not stop at the first one: the
     * entries are ordered newest-first with both kinds interleaved, so bailing out
     * would silently drop everything recorded before an over-quota entry.
     */
    public function cappingSeedEntriesDoesNotDropOlderValuesEntries(): void
    {
        $storage = $this->storage();
        $storage->remember(self::ID, $this->counterExample(['x' => 51], 1), ['x']);

        for ($seed = 2; $seed <= 4; ++$seed) {
            $storage->remember(self::ID, $this->counterExample(['x' => new \stdClass()], $seed), ['x']);
        }

        $entries = $storage->recall(self::ID, ['x']);

        // Two seeds (capped from three) plus the values entry recorded first.
        Assert::same(count($entries), 3);
        Assert::true($entries[0]->isValues());
        Assert::same($entries[0]->arguments, ['x' => 51]);
    }

    public function cappingValuesEntriesDoesNotDropAnOlderSeedEntry(): void
    {
        $storage = $this->storage();
        $storage->remember(self::ID, $this->counterExample(['x' => new \stdClass()], 1), ['x']);

        for ($x = 1; $x <= 9; ++$x) {
            $storage->remember(self::ID, $this->counterExample(['x' => $x], $x + 1), ['x']);
        }

        $entries = $storage->recall(self::ID, ['x']);

        // Eight values (capped from nine) plus the seed entry recorded first.
        Assert::same(count($entries), 9);
        Assert::false($entries[8]->isValues());
        Assert::same($entries[8]->seed, 1);
    }

    /**
     * The corpus is meant to be readable and diffable by hand, and its `entries`
     * field must stay a JSON array — pruning must not leave gapped integer keys
     * that `json_encode()` would turn into an object.
     */
    public function storesAPrettyPrintedDocumentWhoseEntriesStayAJsonList(): void
    {
        $storage = $this->storage();
        $storage->remember(self::ID, $this->counterExample(['x' => 51], 1), ['x']);
        $storage->remember(self::ID, $this->counterExample(['x' => 77], 2), ['x']);
        $storage->remember(self::ID, $this->counterExample(['x' => 99], 3), ['x']);

        // Removes the middle entry, leaving keys 0 and 2 behind unless reindexed.
        $storage->prune(self::ID, CorpusEntry::values(['x' => 77], 2));

        $content = (string) file_get_contents($this->file());
        Assert::string($content)->contains("\n");

        /** @var array<string, mixed> $document */
        $document = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        Assert::true(array_is_list($document['entries']));
    }

    public function pruneRemovesOnlyTheGivenValuesEntry(): void
    {
        $storage = $this->storage();
        $storage->remember(self::ID, $this->counterExample(['x' => 51], 1), ['x']);
        $storage->remember(self::ID, $this->counterExample(['x' => 77], 2), ['x']);

        $storage->prune(self::ID, CorpusEntry::values(['x' => 51], 1));
        $entries = $storage->recall(self::ID, ['x']);

        Assert::same(count($entries), 1);
        Assert::same($entries[0]->arguments, ['x' => 77]);
    }

    public function pruneRemovesASeedEntryByItsSeed(): void
    {
        $storage = $this->storage();
        $storage->remember(self::ID, $this->counterExample(['x' => new \stdClass()], 99), ['x']);

        $storage->prune(self::ID, CorpusEntry::seed(99));

        Assert::same($storage->recall(self::ID, ['x']), []);
    }

    public function pruningTheLastEntryRemovesTheFile(): void
    {
        $storage = $this->storage();
        $storage->remember(self::ID, $this->counterExample(['x' => 51], 1), ['x']);

        Assert::true(is_file($this->file()));

        $storage->prune(self::ID, CorpusEntry::values(['x' => 51], 1));

        Assert::false(is_file($this->file()));
    }

    public function pruningAnUnknownEntryIsANoOp(): void
    {
        $storage = $this->storage();
        $storage->remember(self::ID, $this->counterExample(['x' => 51], 1), ['x']);

        $storage->prune(self::ID, CorpusEntry::values(['x' => 999], 1));

        Assert::same(count($storage->recall(self::ID, ['x'])), 1);
    }

    /**
     * A renamed, reordered or added parameter makes a stored input a different
     * input — replaying it would silently test something else.
     */
    public function dropsValuesEntriesWhoseArgumentNamesNoLongerMatch(): void
    {
        $storage = $this->storage();
        $storage->remember(self::ID, $this->counterExample(['x' => 51], 1), ['x']);

        Assert::same($storage->recall(self::ID, ['renamed']), []);
        Assert::same($storage->recall(self::ID, ['x', 'extra']), []);
    }

    public function ordersRecalledArgumentsByTheCurrentParameterList(): void
    {
        $storage = $this->storage();
        $storage->remember(self::ID, $this->counterExample(['a' => 1, 'b' => 2], 1), ['a', 'b']);

        $entries = $storage->recall(self::ID, ['b', 'a']);

        Assert::same(count($entries), 1);
        Assert::same(array_keys((array) $entries[0]->arguments), ['b', 'a']);
    }

    public function dropsSeedEntriesFromASupersededSequenceEpoch(): void
    {
        $this->writeRaw([
            ['kind' => 'seed', 'seed' => 5, 'epoch' => CorpusStorage::SEQUENCE_EPOCH + 1],
            ['kind' => 'seed', 'seed' => 6, 'epoch' => CorpusStorage::SEQUENCE_EPOCH],
        ]);

        $entries = $this->storage()->recall(self::ID, ['x']);

        Assert::same(count($entries), 1);
        Assert::same($entries[0]->seed, 6);
    }

    /**
     * Values entries carry the input itself, so a shifted generation sequence
     * cannot invalidate them.
     */
    public function keepsValuesEntriesRegardlessOfTheSequenceEpoch(): void
    {
        $this->writeRaw([
            ['kind' => 'values', 'seed' => 1, 'epoch' => CorpusStorage::SEQUENCE_EPOCH + 1, 'args' => ['x' => 51]],
        ]);

        $entries = $this->storage()->recall(self::ID, ['x']);

        Assert::same(count($entries), 1);
        Assert::same($entries[0]->arguments, ['x' => 51]);
    }

    public function ignoresAForeignFormatVersion(): void
    {
        file_put_contents($this->file(), json_encode([
            'format' => CorpusStorage::FORMAT_VERSION + 1,
            'property' => self::ID,
            'entries' => [['kind' => 'seed', 'seed' => 5, 'epoch' => CorpusStorage::SEQUENCE_EPOCH]],
        ], JSON_THROW_ON_ERROR));

        Assert::same($this->storage()->recall(self::ID, ['x']), []);
    }

    public function ignoresCorruptContent(): void
    {
        file_put_contents($this->file(), 'not json at all');

        Assert::same($this->storage()->recall(self::ID, ['x']), []);
    }

    public function ignoresAJsonDocumentThatIsNotACorpus(): void
    {
        file_put_contents($this->file(), '"just a string"');

        Assert::same($this->storage()->recall(self::ID, ['x']), []);
    }

    public function ignoresACorpusWithoutAnEntryList(): void
    {
        file_put_contents($this->file(), json_encode([
            'format' => CorpusStorage::FORMAT_VERSION,
            'property' => self::ID,
        ], JSON_THROW_ON_ERROR));

        Assert::same($this->storage()->recall(self::ID, ['x']), []);
    }

    public function skipsUnusableEntriesButKeepsTheRest(): void
    {
        $this->writeRaw([
            'not an entry',
            ['kind' => 'values', 'seed' => 'not an int', 'epoch' => CorpusStorage::SEQUENCE_EPOCH, 'args' => ['x' => 1]],
            ['kind' => 'nonsense', 'seed' => 1, 'epoch' => CorpusStorage::SEQUENCE_EPOCH],
            ['kind' => 'values', 'seed' => 1, 'epoch' => CorpusStorage::SEQUENCE_EPOCH],
            ['kind' => 'values', 'seed' => 2, 'epoch' => CorpusStorage::SEQUENCE_EPOCH, 'args' => ['x' => ['#' => 'zz']]],
            ['kind' => 'values', 'seed' => 3, 'epoch' => CorpusStorage::SEQUENCE_EPOCH, 'args' => ['x' => 51]],
        ]);

        $entries = $this->storage()->recall(self::ID, ['x']);

        Assert::same(count($entries), 1);
        Assert::same($entries[0]->seed, 3);
    }

    public function rememberCreatesTheDirectory(): void
    {
        $nested = $this->dir . '/nested/db';
        $storage = new CorpusStorage($nested);
        $storage->remember(self::ID, $this->counterExample(['x' => 1], 9), ['x']);

        Assert::true(is_dir($nested));
        Assert::same((new CorpusStorage($nested))->recall(self::ID, ['x'])[0]->seed, 9);
    }

    public function trailingSlashesInTheDirectoryDoNotSplitTheRecord(): void
    {
        (new CorpusStorage($this->dir . '/'))->remember(self::ID, $this->counterExample(['x' => 1], 9), ['x']);

        Assert::same(count($this->storage()->recall(self::ID, ['x'])), 1);
    }

    public function separatePropertiesGetSeparateRecords(): void
    {
        $storage = $this->storage();
        $storage->remember('A::a', $this->counterExample(['x' => 1], 1), ['x']);
        $storage->remember('B::b', $this->counterExample(['x' => 2], 2), ['x']);

        Assert::same($storage->recall('A::a', ['x'])[0]->arguments, ['x' => 1]);
        Assert::same($storage->recall('B::b', ['x'])[0]->arguments, ['x' => 2]);
    }

    public function fromEnvIsNullWhenUnset(): void
    {
        putenv('PROPERTY_DB');

        Assert::null(CorpusStorage::fromEnv());
    }

    public function fromEnvIsNullWhenEmpty(): void
    {
        putenv('PROPERTY_DB=');

        try {
            Assert::null(CorpusStorage::fromEnv());
        } finally {
            putenv('PROPERTY_DB');
        }
    }

    public function fromEnvBuildsStorageWhenSet(): void
    {
        putenv('PROPERTY_DB=' . $this->dir);

        try {
            $storage = CorpusStorage::fromEnv();

            Assert::instanceOf($storage, CorpusStorage::class);
            \assert($storage instanceof CorpusStorage);
            $storage->remember(self::ID, $this->counterExample(['x' => 3], 3), ['x']);

            Assert::same($storage->recall(self::ID, ['x'])[0]->arguments, ['x' => 3]);
        } finally {
            putenv('PROPERTY_DB');
        }
    }

    #[BeforeTest]
    public function setUpDirectory(): void
    {
        $this->dir = sys_get_temp_dir() . '/prop-corpus-' . bin2hex(random_bytes(6));
        mkdir($this->dir, 0o777, true);
    }

    #[AfterTest]
    public function tearDownDirectory(): void
    {
        $this->removeRecursively($this->dir);
    }

    private function storage(): CorpusStorage
    {
        return new CorpusStorage($this->dir);
    }

    private function file(): string
    {
        return $this->dir . '/' . sha1(self::ID) . '.json';
    }

    /**
     * @param array<string, mixed> $arguments
     */
    private function counterExample(array $arguments, int $seed): CounterExample
    {
        return new CounterExample(
            seed: $seed,
            runsBeforeFailure: 0,
            originalArguments: $arguments,
            shrunkArguments: $arguments,
        );
    }

    /**
     * @param list<mixed> $entries
     */
    private function writeRaw(array $entries): void
    {
        file_put_contents($this->file(), json_encode([
            'format' => CorpusStorage::FORMAT_VERSION,
            'property' => self::ID,
            'entries' => $entries,
        ], JSON_THROW_ON_ERROR));
    }

    private function removeRecursively(string $path): void
    {
        foreach (glob($path . '/*') ?: [] as $child) {
            is_dir($child) ? $this->removeRecursively($child) : @unlink($child);
        }

        @rmdir($path);
    }
}
