<?php

declare(strict_types=1);

namespace NeNeRecords\Media;

interface MediaRepositoryInterface
{
    public function save(Media $media): int;

    public function findById(int $id): ?Media;

    /** @return list<Media> */
    public function list(): array;

    public function updateAltText(int $id, ?string $altText): void;

    /**
     * Reverse-lookup: find every entity field whose stored value references the
     * given media URL (image / file fields and markdown bodies). Org-scoped.
     *
     * @return list<MediaUsage>
     */
    public function findUsages(string $url): array;

    public function delete(int $id): void;
}
