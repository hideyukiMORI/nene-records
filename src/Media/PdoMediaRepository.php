<?php

declare(strict_types=1);

namespace NeNeRecords\Media;

use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\Http\RequestScopedHolder;

final readonly class PdoMediaRepository implements MediaRepositoryInterface
{
    /**
     * @param RequestScopedHolder<int> $orgId
     */
    public function __construct(
        private DatabaseQueryExecutorInterface $query,
        private readonly RequestScopedHolder $orgId,
    ) {
    }

    public function save(Media $media): int
    {
        $this->query->execute(
            'INSERT INTO media (organization_id, original_name, stored_name, mime_type, size, url, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)',
            [$this->orgId->get(), $media->originalName, $media->storedName, $media->mimeType, $media->size, $media->url, $media->createdAt],
        );

        return $this->query->lastInsertId();
    }

    public function findById(int $id): ?Media
    {
        $row = $this->query->fetchOne(
            'SELECT id, original_name, stored_name, mime_type, size, url, created_at FROM media WHERE id = ? AND organization_id = ?',
            [$id, $this->orgId->get()],
        );

        if ($row === null) {
            return null;
        }

        return $this->mapRow($row);
    }

    /** @return list<Media> */
    public function list(): array
    {
        $rows = $this->query->fetchAll(
            'SELECT id, original_name, stored_name, mime_type, size, url, created_at FROM media WHERE organization_id = ? ORDER BY created_at DESC',
            [$this->orgId->get()],
        );

        return array_map(fn (array $row): Media => $this->mapRow($row), $rows);
    }

    public function delete(int $id): void
    {
        $this->query->execute('DELETE FROM media WHERE id = ? AND organization_id = ?', [$id, $this->orgId->get()]);
    }

    /** @param array<string, mixed> $row */
    private function mapRow(array $row): Media
    {
        return new Media(
            id: (int) $row['id'],
            originalName: (string) $row['original_name'],
            storedName: (string) $row['stored_name'],
            mimeType: (string) $row['mime_type'],
            size: (int) $row['size'],
            url: (string) $row['url'],
            createdAt: (string) $row['created_at'],
        );
    }
}
