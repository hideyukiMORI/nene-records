<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\IntField;

use NeNeRecords\IntField\IntField;
use NeNeRecords\IntField\IntFieldNotFoundException;
use NeNeRecords\IntField\IntFieldRepositoryInterface;

final class InMemoryIntFieldRepository implements IntFieldRepositoryInterface
{
    /** @var array<int, IntField> */
    private array $fields;

    /** @var array<int, true> */
    private array $deletedIds;

    private int $nextId;

    /** @param list<IntField> $seed */
    public function __construct(array $seed = [])
    {
        $this->fields = [];
        $this->deletedIds = [];
        $this->nextId = 1;

        foreach ($seed as $textField) {
            $id = $textField->id;
            if ($id !== null) {
                $this->fields[$id] = $textField;
                $this->nextId = max($this->nextId, $id + 1);
            }
        }
    }

    public function findById(int $id): ?IntField
    {
        if (isset($this->deletedIds[$id])) {
            return null;
        }

        return $this->fields[$id] ?? null;
    }

    /** @return list<IntField> */
    public function findAll(int $limit, int $offset): array
    {
        $active = [];

        foreach ($this->fields as $id => $textField) {
            if (!isset($this->deletedIds[$id])) {
                $active[] = $textField;
            }
        }

        usort($active, static fn (IntField $a, IntField $b): int => ($a->id ?? 0) <=> ($b->id ?? 0));

        return array_slice($active, $offset, $limit);
    }

    public function save(IntField $intField): int
    {
        $id = $this->nextId++;
        $this->fields[$id] = new IntField(
            entityId: $intField->entityId,
            fieldKey: $intField->fieldKey,
            value: $intField->value,
            id: $id,
        );

        return $id;
    }

    public function update(IntField $intField): void
    {
        $id = $intField->id;

        if ($id === null) {
            return;
        }

        if ($this->findById($id) === null) {
            throw new IntFieldNotFoundException($id);
        }

        $this->fields[$id] = $intField;
    }

    public function delete(int $id): void
    {
        if (!isset($this->fields[$id]) || isset($this->deletedIds[$id])) {
            throw new IntFieldNotFoundException($id);
        }

        unset($this->fields[$id]);
        $this->deletedIds[$id] = true;
    }
}
