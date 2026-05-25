<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\EntityTag;

use NeNeRecords\EntityTag\EntityTagRepositoryInterface;
use NeNeRecords\EntityTag\ListEntityTagItem;

final class InMemoryEntityTagRepository implements EntityTagRepositoryInterface
{
    /** @var array<string, true> "entityId:tagId" => attached */
    private array $attachments;

    /** @var array<int, ListEntityTagItem> tagId => item */
    private array $tagsById;

    public function __construct()
    {
        $this->attachments = [];
        $this->tagsById = [];
    }

    public function seedTag(int $id, string $slug, string $name): void
    {
        $this->tagsById[$id] = new ListEntityTagItem(id: $id, slug: $slug, name: $name);
    }

    /** @return list<ListEntityTagItem> */
    public function findTagsByEntityId(int $entityId): array
    {
        $items = [];

        foreach ($this->attachments as $key => $_attached) {
            [$attachedEntityId, $tagId] = array_map('intval', explode(':', $key, 2));

            if ($attachedEntityId !== $entityId) {
                continue;
            }

            $tag = $this->tagsById[$tagId] ?? null;

            if ($tag !== null) {
                $items[] = $tag;
            }
        }

        usort($items, static fn (ListEntityTagItem $a, ListEntityTagItem $b): int => $a->id <=> $b->id);

        return $items;
    }

    public function isAttached(int $entityId, int $tagId): bool
    {
        return isset($this->attachments["{$entityId}:{$tagId}"]);
    }

    public function attach(int $entityId, int $tagId): void
    {
        $this->attachments["{$entityId}:{$tagId}"] = true;
    }

    public function detach(int $entityId, int $tagId): void
    {
        unset($this->attachments["{$entityId}:{$tagId}"]);
    }
}
