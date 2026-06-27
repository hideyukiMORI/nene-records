<?php

declare(strict_types=1);

namespace NeNeRecords\UrlRedirect;

use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\Http\RequestScopedHolder;

final readonly class PdoUrlRedirectRepository implements UrlRedirectRepositoryInterface
{
    /**
     * @param RequestScopedHolder<int> $orgId
     */
    public function __construct(
        private DatabaseQueryExecutorInterface $query,
        private RequestScopedHolder $orgId,
    ) {
    }

    public function findTargetBySource(string $sourcePath): ?string
    {
        $row = $this->query->fetchOne(
            'SELECT target_path FROM url_redirects WHERE organization_id = ? AND source_path = ?',
            [$this->orgId->get(), $sourcePath],
        );

        if ($row === null) {
            return null;
        }

        return (string) $row['target_path'];
    }

    public function save(string $sourcePath, string $targetPath): void
    {
        $this->query->execute(
            'INSERT INTO url_redirects (organization_id, source_path, target_path, created_at)
             VALUES (?, ?, ?, NOW())
             ON DUPLICATE KEY UPDATE target_path = VALUES(target_path)',
            [$this->orgId->get(), $sourcePath, $targetPath],
        );
    }

    public function recordMove(string $oldPath, string $newPath): void
    {
        if ($oldPath === $newPath) {
            return;
        }

        $org = $this->orgId->get();

        // 1. Compress chains: anything that pointed to the old path now points to new.
        $this->query->execute(
            'UPDATE url_redirects SET target_path = ? WHERE organization_id = ? AND target_path = ?',
            [$newPath, $org, $oldPath],
        );

        // 2. Record old → new.
        $this->query->execute(
            'INSERT INTO url_redirects (organization_id, source_path, target_path, created_at)
             VALUES (?, ?, ?, NOW())
             ON DUPLICATE KEY UPDATE target_path = VALUES(target_path)',
            [$org, $oldPath, $newPath],
        );

        // 3. The new path is now a live page — it must never be a redirect source
        //    (this also clears any self-redirect produced by step 1, breaking loops).
        $this->query->execute(
            'DELETE FROM url_redirects WHERE organization_id = ? AND source_path = ?',
            [$org, $newPath],
        );
    }
}
