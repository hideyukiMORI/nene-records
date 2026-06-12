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

    private const COLUMNS = 'id, original_name, stored_name, mime_type, size, url, storage_key, width, height, alt_text, created_at';

    public function save(Media $media): int
    {
        $this->query->execute(
            'INSERT INTO media (organization_id, original_name, stored_name, mime_type, size, url, storage_key, width, height, alt_text, created_at)'
            . ' VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $this->orgId->get(),
                $media->originalName,
                $media->storedName,
                $media->mimeType,
                $media->size,
                $media->url,
                $media->storageKey,
                $media->width,
                $media->height,
                $media->altText,
                $media->createdAt,
            ],
        );

        return $this->query->lastInsertId();
    }

    public function findById(int $id): ?Media
    {
        $row = $this->query->fetchOne(
            'SELECT ' . self::COLUMNS . ' FROM media WHERE id = ? AND organization_id = ?',
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
            'SELECT ' . self::COLUMNS . ' FROM media WHERE organization_id = ? ORDER BY created_at DESC',
            [$this->orgId->get()],
        );

        return array_map(fn (array $row): Media => $this->mapRow($row), $rows);
    }

    public function updateAltText(int $id, ?string $altText): void
    {
        $this->query->execute(
            'UPDATE media SET alt_text = ? WHERE id = ? AND organization_id = ?',
            [$altText, $id, $this->orgId->get()],
        );
    }

    /** @return list<MediaUsage> */
    public function findUsages(string $url): array
    {
        // Match the media URL anywhere inside a stored field value: image/file
        // fields hold the bare URL, markdown bodies hold it inside `![](...)`.
        // Escape LIKE metacharacters so filenames containing _ or % stay literal.
        $pattern = '%' . addcslashes($url, '\\%_') . '%';

        $rows = $this->query->fetchAll(
            "SELECT
                e.id AS entity_id,
                MAX(e.slug) AS entity_slug,
                MAX(e.status) AS status,
                MAX(et.slug) AS entity_type_slug,
                tf.field_key AS field_key,
                MAX(tf_title.value) AS title
             FROM text_fields tf
             JOIN entities e ON e.id = tf.entity_id AND e.is_deleted = 0
             JOIN entity_types et ON et.id = e.entity_type_id
             LEFT JOIN text_fields tf_title
                ON tf_title.entity_id = e.id AND tf_title.field_key = 'title' AND tf_title.is_deleted = 0
             WHERE tf.organization_id = ?
               AND tf.is_deleted = 0
               AND tf.value LIKE ? ESCAPE '\\\\'
             GROUP BY e.id, tf.field_key
             ORDER BY e.id, tf.field_key",
            [$this->orgId->get(), $pattern],
        );

        return array_map(
            static fn (array $row): MediaUsage => new MediaUsage(
                entityId: (int) $row['entity_id'],
                entityTypeSlug: (string) $row['entity_type_slug'],
                entitySlug: (string) $row['entity_slug'],
                status: (string) $row['status'],
                fieldKey: (string) $row['field_key'],
                title: $row['title'] !== null ? (string) $row['title'] : null,
            ),
            $rows,
        );
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
            storageKey: (string) $row['storage_key'],
            width: $row['width'] !== null ? (int) $row['width'] : null,
            height: $row['height'] !== null ? (int) $row['height'] : null,
            altText: $row['alt_text'] !== null ? (string) $row['alt_text'] : null,
        );
    }
}
