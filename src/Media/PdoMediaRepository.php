<?php

declare(strict_types=1);

namespace NeNeRecords\Media;

use Nene2\Database\DatabaseQueryExecutorInterface;

final readonly class PdoMediaRepository implements MediaRepositoryInterface
{
    public function __construct(
        private DatabaseQueryExecutorInterface $query,
    ) {
    }

    public function save(Media $media): int
    {
        $this->query->execute(
            'INSERT INTO media (original_name, stored_name, mime_type, size, url, created_at) VALUES (?, ?, ?, ?, ?, ?)',
            [$media->originalName, $media->storedName, $media->mimeType, $media->size, $media->url, $media->createdAt],
        );

        return $this->query->lastInsertId();
    }

    public function findById(int $id): ?Media
    {
        $row = $this->query->fetchOne(
            'SELECT id, original_name, stored_name, mime_type, size, url, created_at FROM media WHERE id = ?',
            [$id],
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
            'SELECT id, original_name, stored_name, mime_type, size, url, created_at FROM media ORDER BY created_at DESC',
            [],
        );

        return array_map(fn (array $row): Media => $this->mapRow($row), $rows);
    }

    public function delete(int $id): void
    {
        $this->query->execute('DELETE FROM media WHERE id = ?', [$id]);
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
