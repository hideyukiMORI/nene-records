<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\BoolField;

use NeNeRecords\BoolField\BoolField;
use NeNeRecords\BoolField\BoolFieldNotFoundException;
use NeNeRecords\BoolField\BoolFieldRepositoryInterface;

final class InMemoryBoolFieldRepository implements BoolFieldRepositoryInterface
{
    /** @var array<int, BoolField> */
    private array $fields;

    /** @var array<int, true> */
    private array $deletedIds;

    private int $nextId;

    /** @param list<BoolField> $seed */
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

    public function findById(int $id): ?BoolField
    {
        if (isset($this->deletedIds[$id])) {
            return null;
        }

        return $this->fields[$id] ?? null;
    }

    /** @return list<BoolField> */
    public function findAll(int $limit, int $offset): array
    {
        $active = [];

        foreach ($this->fields as $id => $textField) {
            if (!isset($this->deletedIds[$id])) {
                $active[] = $textField;
            }
        }

        usort($active, static fn (BoolField $a, BoolField $b): int => ($a->id ?? 0) <=> ($b->id ?? 0));

        return array_slice($active, $offset, $limit);
    }

    public function save(BoolField $intField): int
    {
        $id = $this->nextId++;
        $this->fields[$id] = new BoolField(
            entityId: $intField->entityId,
            fieldKey: $intField->fieldKey,
            value: $intField->value,
            id: $id,
        );

        return $id;
    }

    public function update(BoolField $intField): void
    {
        $id = $intField->id;

        if ($id === null) {
            return;
        }

        if ($this->findById($id) === null) {
            throw new BoolFieldNotFoundException($id);
        }

        $this->fields[$id] = $intField;
    }

    public function delete(int $id): void
    {
        if (!isset($this->fields[$id]) || isset($this->deletedIds[$id])) {
            throw new BoolFieldNotFoundException($id);
        }

        unset($this->fields[$id]);
        $this->deletedIds[$id] = true;
    }
}
