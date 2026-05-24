<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\Tag;

use NeNeRecords\Tag\Tag;
use NeNeRecords\Tag\TagRepositoryInterface;

final class InMemoryTagRepository implements TagRepositoryInterface
{
    /** @var array<int, Tag> */
    private array $byId;

    /** @var array<string, int> */
    private array $slugToId;

    private int $nextId;

    /** @param list<Tag> $seed */
    public function __construct(array $seed = [])
    {
        $this->byId = [];
        $this->slugToId = [];
        $this->nextId = 1;

        foreach ($seed as $tag) {
            if ($tag->id !== null) {
                $id = $tag->id;
                $stored = new Tag(slug: $tag->slug, name: $tag->name, id: $id);
                $this->byId[$id] = $stored;
                $this->slugToId[$stored->slug] = $id;
                $this->nextId = max($this->nextId, $id + 1);
            }
        }
    }

    public function findById(int $id): ?Tag
    {
        return $this->byId[$id] ?? null;
    }

    public function findBySlug(string $slug): ?Tag
    {
        $id = $this->slugToId[$slug] ?? null;

        if ($id === null) {
            return null;
        }

        return $this->byId[$id] ?? null;
    }

    /** @return list<Tag> */
    public function findAll(int $limit, int $offset): array
    {
        $tags = array_values($this->byId);
        usort($tags, static fn (Tag $a, Tag $b): int => ($a->id ?? 0) <=> ($b->id ?? 0));

        return array_slice($tags, $offset, $limit);
    }

    public function save(Tag $tag): int
    {
        $id = $this->nextId++;
        $stored = new Tag(slug: $tag->slug, name: $tag->name, id: $id);
        $this->byId[$id] = $stored;
        $this->slugToId[$stored->slug] = $id;

        return $id;
    }

    public function update(Tag $tag): void
    {
        $id = $tag->id;

        if ($id === null || !isset($this->byId[$id])) {
            return;
        }

        $old = $this->byId[$id];
        unset($this->slugToId[$old->slug]);

        $this->slugToId[$tag->slug] = $id;
        $this->byId[$id] = $tag;
    }

    public function delete(int $id): void
    {
        $tag = $this->byId[$id] ?? null;

        if ($tag === null) {
            return;
        }

        unset($this->slugToId[$tag->slug]);
        unset($this->byId[$id]);
    }
}
