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
}
