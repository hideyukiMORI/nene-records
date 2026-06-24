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

    /** @return array<string, string> test helper: the full redirect map */
    public function all(): array
    {
        return $this->map;
    }
}
