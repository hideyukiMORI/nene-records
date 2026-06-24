<?php

declare(strict_types=1);

namespace NeNeRecords\UrlRedirect;

interface UrlRedirectRepositoryInterface
{
    /** Resolve the 301 target for a source path within the active org, or null. */
    public function findTargetBySource(string $sourcePath): ?string;

    /**
     * Upsert a redirect (one per org + source path). Re-importing the same
     * source refreshes its target rather than duplicating.
     */
    public function save(string $sourcePath, string $targetPath): void;
}
