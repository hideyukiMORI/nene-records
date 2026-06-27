<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\UrlRedirect;

use NeNeRecords\UrlRedirect\UrlRedirectRepositoryInterface;

final class InMemoryUrlRedirectRepository implements UrlRedirectRepositoryInterface
{
    /** @var array<string, string> source path → target path */
    private array $map = [];

    public function findTargetBySource(string $sourcePath): ?string
    {
        return $this->map[$sourcePath] ?? null;
    }

    public function save(string $sourcePath, string $targetPath): void
    {
        $this->map[$sourcePath] = $targetPath;
    }

    public function recordMove(string $oldPath, string $newPath): void
    {
        if ($oldPath === $newPath) {
            return;
        }

        // 1. Re-point chains targeting the old path to the new path.
        foreach ($this->map as $source => $target) {
            if ($target === $oldPath) {
                $this->map[$source] = $newPath;
            }
        }

        // 2. Record old → new.
        $this->map[$oldPath] = $newPath;

        // 3. The new path is now live — drop any redirect from it (incl. self-redirect).
        unset($this->map[$newPath]);
    }

    /** @return array<string, string> test helper: the full redirect map */
    public function all(): array
    {
        return $this->map;
    }
}
