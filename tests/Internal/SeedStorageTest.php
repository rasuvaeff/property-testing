<?php

declare(strict_types=1);

namespace Rasuvaeff\PropertyTesting\Tests\Internal;

use Rasuvaeff\PropertyTesting\Internal\SeedStorage;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Test;

#[Test]
#[Covers(SeedStorage::class)]
final class SeedStorageTest
{
    public function remembersAndRecallsASeed(): void
    {
        $dir = $this->tempDir();

        try {
            $storage = new SeedStorage($dir);

            Assert::same($storage->recall('X::y'), null);

            $storage->remember('X::y', 4242);

            Assert::same($storage->recall('X::y'), 4242);
        } finally {
            $this->cleanup($dir);
        }
    }

    public function remembersNegativeSeeds(): void
    {
        $dir = $this->tempDir();

        try {
            $storage = new SeedStorage($dir);
            $storage->remember('X::y', -5);

            Assert::same($storage->recall('X::y'), -5);
        } finally {
            $this->cleanup($dir);
        }
    }

    public function forgetRemovesTheRecord(): void
    {
        $dir = $this->tempDir();

        try {
            $storage = new SeedStorage($dir);
            $storage->remember('X::y', 1);
            $storage->forget('X::y');

            Assert::same($storage->recall('X::y'), null);
            // Forgetting a missing record is a no-op, not an error.
            $storage->forget('X::y');
        } finally {
            $this->cleanup($dir);
        }
    }

    public function recallReturnsNullForCorruptContent(): void
    {
        $dir = $this->tempDir();

        try {
            file_put_contents($dir . '/' . sha1('X::y') . '.seed', 'not-a-number');

            Assert::same((new SeedStorage($dir))->recall('X::y'), null);
        } finally {
            $this->cleanup($dir);
        }
    }

    public function rememberCreatesTheDirectory(): void
    {
        $base = $this->tempDir();
        $dir = $base . '/nested/db';

        try {
            (new SeedStorage($dir))->remember('X::y', 9);

            Assert::same(is_dir($dir), true);
            Assert::same((new SeedStorage($dir))->recall('X::y'), 9);
        } finally {
            $this->cleanup($dir);
            @rmdir($base . '/nested');
            @rmdir($base);
        }
    }

    public function fromEnvIsNullWhenUnset(): void
    {
        putenv('PROPERTY_DB');

        Assert::same(SeedStorage::fromEnv(), null);
    }

    public function fromEnvBuildsStorageWhenSet(): void
    {
        $dir = $this->tempDir();
        putenv('PROPERTY_DB=' . $dir);

        try {
            $storage = SeedStorage::fromEnv();

            Assert::instanceOf($storage, SeedStorage::class);
            \assert($storage instanceof SeedStorage);
            $storage->remember('X::y', 3);

            Assert::same($storage->recall('X::y'), 3);
        } finally {
            putenv('PROPERTY_DB');
            $this->cleanup($dir);
        }
    }

    private function tempDir(): string
    {
        $dir = sys_get_temp_dir() . '/prop-seed-' . bin2hex(random_bytes(6));
        mkdir($dir, 0o777, true);

        return $dir;
    }

    private function cleanup(string $dir): void
    {
        foreach (glob($dir . '/*') ?: [] as $file) {
            @unlink($file);
        }

        @rmdir($dir);
    }
}
