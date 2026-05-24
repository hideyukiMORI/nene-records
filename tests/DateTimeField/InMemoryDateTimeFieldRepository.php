<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\DateTimeField;

use NeNeRecords\DateTimeField\DateTimeField;
use NeNeRecords\DateTimeField\DateTimeFieldNotFoundException;
use NeNeRecords\DateTimeField\DateTimeFieldRepositoryInterface;

final class InMemoryDateTimeFieldRepository implements DateTimeFieldRepositoryInterface
{
    /** @var array<int, DateTimeField> */
    private array $fields;

    /** @var array<int, true> */
    private array $deletedIds;

    private int $nextId;

    /** @param list<DateTimeField> $seed */
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

    public function findById(int $id): ?DateTimeField
    {
        if (isset($this->deletedIds[$id])) {
            return null;
        }

        return $this->fields[$id] ?? null;
    }

    /** @return list<DateTimeField> */
    public function findAll(int $limit, int $offset): array
    {
        $active = [];

        foreach ($this->fields as $id => $textField) {
            if (!isset($this->deletedIds[$id])) {
                $active[] = $textField;
            }
        }

        usort($active, static fn (DateTimeField $a, DateTimeField $b): int => ($a->id ?? 0) <=> ($b->id ?? 0));

        return array_slice($active, $offset, $limit);
    }

    /** @return list<DateTimeField> */
    public function findByEntityId(int $entityId, int $limit, int $offset): array
    {
        $active = [];

        foreach ($this->fields as $id => $textField) {
            if (!isset($this->deletedIds[$id]) && $textField->entityId === $entityId) {
                $active[] = $textField;
            }
        }

        usort($active, static fn (DateTimeField $a, DateTimeField $b): int => ($a->id ?? 0) <=> ($b->id ?? 0));

        return array_slice($active, $offset, $limit);
    }

    public function save(DateTimeField $intField): int
    {
        $id = $this->nextId++;
        $this->fields[$id] = new DateTimeField(
            entityId: $intField->entityId,
            fieldKey: $intField->fieldKey,
            value: $intField->value,
            id: $id,
        );

        return $id;
    }

    public function update(DateTimeField $intField): void
    {
        $id = $intField->id;

        if ($id === null) {
            return;
        }

        if ($this->findById($id) === null) {
            throw new DateTimeFieldNotFoundException($id);
        }

        $this->fields[$id] = $intField;
    }

    public function delete(int $id): void
    {
        if (!isset($this->fields[$id]) || isset($this->deletedIds[$id])) {
            throw new DateTimeFieldNotFoundException($id);
        }

        unset($this->fields[$id]);
        $this->deletedIds[$id] = true;
    }
}
