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

    /**
     * Record a permalink change old → new, keeping the store self-healing (#673):
     * (1) re-point chains — anything targeting old now targets new; (2) upsert
     * old → new; (3) drop any redirect whose source is the now-live new path
     * (loop guard — also clears a self-redirect from step 1). No-op when equal.
     */
    public function recordMove(string $oldPath, string $newPath): void;
}
