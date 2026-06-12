<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\Media;

use NeNeRecords\Media\Media;
use NeNeRecords\Media\MediaRepositoryInterface;

final class InMemoryMediaRepository implements MediaRepositoryInterface
{
    /** @var array<int, Media> */
    private array $store = [];
    private int $nextId = 1;

    /** @param list<Media> $initial */
    public function __construct(array $initial = [])
    {
        foreach ($initial as $media) {
            $id = (int) $media->id;
            $this->store[$id] = $media;
            if ($id >= $this->nextId) {
                $this->nextId = $id + 1;
            }
        }
    }

    public function save(Media $media): int
    {
        $id = $this->nextId++;
        $this->store[$id] = new Media(
            id: $id,
            originalName: $media->originalName,
            storedName: $media->storedName,
            mimeType: $media->mimeType,
            size: $media->size,
            url: $media->url,
            createdAt: $media->createdAt,
            storageKey: $media->storageKey,
            width: $media->width,
            height: $media->height,
            altText: $media->altText,
        );

        return $id;
    }

    public function findById(int $id): ?Media
    {
        return $this->store[$id] ?? null;
    }

    /** @return list<Media> */
    public function list(): array
    {
        $items = array_values($this->store);
        usort($items, static fn (Media $a, Media $b): int => strcmp($b->createdAt, $a->createdAt));

        return $items;
    }

    public function updateAltText(int $id, ?string $altText): void
    {
        $media = $this->store[$id] ?? null;

        if ($media === null) {
            return;
        }

        $this->store[$id] = new Media(
            id: $media->id,
            originalName: $media->originalName,
            storedName: $media->storedName,
            mimeType: $media->mimeType,
            size: $media->size,
            url: $media->url,
            createdAt: $media->createdAt,
            storageKey: $media->storageKey,
            width: $media->width,
            height: $media->height,
            altText: $altText,
        );
    }

    public function delete(int $id): void
    {
        unset($this->store[$id]);
    }
}
