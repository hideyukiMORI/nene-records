<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\BlocksField;

use NeNeRecords\BlocksField\BlocksField;
use NeNeRecords\BlocksField\BlocksFieldNotFoundException;
use NeNeRecords\BlocksField\BlocksFieldRepositoryInterface;
use NeNeRecords\Entity\EntityRepositoryInterface;

final class InMemoryBlocksFieldRepository implements BlocksFieldRepositoryInterface
{
    /** @var array<int, BlocksField> */
    private array $fields;

    /** @var array<int, true> */
    private array $deletedIds;

    private int $nextId;

    /**
     * @param list<BlocksField> $seed
     */
    public function __construct(
        array $seed = [],
        private ?EntityRepositoryInterface $entities = null,
    ) {
        $this->fields = [];
        $this->deletedIds = [];
        $this->nextId = 1;

        foreach ($seed as $blocksField) {
            $id = $blocksField->id;
            if ($id !== null) {
                $this->fields[$id] = $blocksField;
                $this->nextId = max($this->nextId, $id + 1);
            }
        }
    }

    public function findById(int $id): ?BlocksField
    {
        if (isset($this->deletedIds[$id])) {
            return null;
        }

        return $this->fields[$id] ?? null;
    }

    /** @return list<BlocksField> */
    public function findAll(int $limit, int $offset, ?string $locale = null): array
    {
        $active = [];

        foreach ($this->fields as $id => $blocksField) {
            if (!isset($this->deletedIds[$id])) {
                if ($locale !== null && $blocksField->locale !== $locale) {
                    continue;
                }

                $active[] = $blocksField;
            }
        }

        usort($active, static fn (BlocksField $a, BlocksField $b): int => ($a->id ?? 0) <=> ($b->id ?? 0));

        return array_slice($active, $offset, $limit);
    }

    /** @return list<BlocksField> */
    public function findByEntityId(int $entityId, int $limit, int $offset, ?string $locale = null): array
    {
        $active = [];

        foreach ($this->fields as $id => $blocksField) {
            if (!isset($this->deletedIds[$id]) && $blocksField->entityId === $entityId) {
                if ($locale !== null && $blocksField->locale !== $locale) {
                    continue;
                }

                $active[] = $blocksField;
            }
        }

        usort($active, static fn (BlocksField $a, BlocksField $b): int => ($a->id ?? 0) <=> ($b->id ?? 0));

        return array_slice($active, $offset, $limit);
    }

    /** @return list<BlocksField> */
    public function findByEntityTypeId(int $entityTypeId, int $limit, int $offset, ?string $locale = null): array
    {
        $active = [];

        foreach ($this->fields as $id => $blocksField) {
            if (isset($this->deletedIds[$id])) {
                continue;
            }

            if ($this->entities === null) {
                continue;
            }

            $entity = $this->entities->findById($blocksField->entityId);

            if ($entity !== null && $entity->entityTypeId === $entityTypeId) {
                if ($locale !== null && $blocksField->locale !== $locale) {
                    continue;
                }

                $active[] = $blocksField;
            }
        }

        usort($active, static fn (BlocksField $a, BlocksField $b): int => ($a->id ?? 0) <=> ($b->id ?? 0));

        return array_slice($active, $offset, $limit);
    }

    /**
     * @param list<int> $entityIds
     * @return list<BlocksField>
     */
    public function findByEntityIds(array $entityIds): array
    {
        if ($entityIds === []) {
            return [];
        }

        $active = [];

        foreach ($this->fields as $id => $blocksField) {
            if (!isset($this->deletedIds[$id]) && in_array($blocksField->entityId, $entityIds, true)) {
                $active[] = $blocksField;
            }
        }

        usort($active, static fn (BlocksField $a, BlocksField $b): int => ($a->id ?? 0) <=> ($b->id ?? 0));

        return $active;
    }

    public function save(BlocksField $blocksField): int
    {
        $id = $this->nextId++;
        $this->fields[$id] = new BlocksField(
            entityId: $blocksField->entityId,
            fieldKey: $blocksField->fieldKey,
            value: $blocksField->value,
            id: $id,
            locale: $blocksField->locale,
        );

        return $id;
    }

    public function update(BlocksField $blocksField): void
    {
        $id = $blocksField->id;

        if ($id === null) {
            return;
        }

        if ($this->findById($id) === null) {
            throw new BlocksFieldNotFoundException($id);
        }

        $this->fields[$id] = $blocksField;
    }

    public function delete(int $id): void
    {
        if (!isset($this->fields[$id]) || isset($this->deletedIds[$id])) {
            throw new BlocksFieldNotFoundException($id);
        }

        unset($this->fields[$id]);
        $this->deletedIds[$id] = true;
    }
}
