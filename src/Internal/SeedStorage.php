<?php

declare(strict_types=1);

namespace Rasuvaeff\PropertyTesting\Internal;

/**
 * Opt-in on-disk record of the last failing seed per property, so a falsified
 * property re-runs that seed first on the next run (fast regression replay).
 *
 * Only the seed is stored — never the generated values, which may be objects or
 * closures that do not serialise — because re-running the seed reproduces the
 * same draw deterministically. Enabled solely when the `PROPERTY_DB` environment
 * variable points at a directory; otherwise storage is off and nothing is
 * written. One file per property (`<sha1(id)>.seed`) keeps it gitignore-friendly.
 *
 * @internal
 */
final readonly class SeedStorage
{
    public function __construct(
        private string $directory,
    ) {}

    /**
     * The storage configured by `PROPERTY_DB` (a directory path), or null when
     * the variable is unset/empty (storage disabled — no files are written).
     */
    public static function fromEnv(): ?self
    {
        $directory = getenv('PROPERTY_DB');

        if ($directory === false || $directory === '') {
            return null;
        }

        return new self($directory);
    }

    /**
     * The recorded failing seed for $id, or null when none is stored (or the
     * stored file is unreadable/corrupt).
     */
    public function recall(string $id): ?int
    {
        $file = $this->path($id);

        if (!is_file($file)) {
            return null;
        }

        $content = file_get_contents($file);

        if ($content === false || preg_match('/^-?\d+$/', trim($content)) !== 1) {
            return null;
        }

        return (int) trim($content);
    }

    public function remember(string $id, int $seed): void
    {
        if (!is_dir($this->directory)) {
            mkdir($this->directory, 0o777, true);
        }

        file_put_contents($this->path($id), (string) $seed);
    }

    public function forget(string $id): void
    {
        $file = $this->path($id);

        if (is_file($file)) {
            unlink($file);
        }
    }

    private function path(string $id): string
    {
        return rtrim($this->directory, '/') . '/' . sha1($id) . '.seed';
    }
}
