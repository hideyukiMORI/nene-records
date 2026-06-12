<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\FieldDef;

use DateTimeImmutable;
use NeNeRecords\FieldDef\FieldDef;
use NeNeRecords\FieldDef\FieldDefRepositoryInterface;

final class InMemoryFieldDefRepository implements FieldDefRepositoryInterface
{
    /** @var array<int, FieldDef> */
    private array $byId;

    /** @var array<string, int> */
    private array $entityTypeFieldKeyToId;

    private int $nextId;

    /** @param list<FieldDef> $seed */
    public function __construct(array $seed = [])
    {
        $this->byId = [];
        $this->entityTypeFieldKeyToId = [];
        $this->nextId = 1;

        foreach ($seed as $fieldDef) {
            if ($fieldDef->id !== null) {
                $id = $fieldDef->id;
                $stored = new FieldDef(
                    entityTypeId: $fieldDef->entityTypeId,
                    fieldKey: $fieldDef->fieldKey,
                    dataType: $fieldDef->dataType,
                    id: $id,
                    isDeleted: $fieldDef->isDeleted,
                    deletedAt: $fieldDef->deletedAt,
                    targetEntityTypeId: $fieldDef->targetEntityTypeId,
                    cardinality: $fieldDef->cardinality,
                    region: $fieldDef->region,
                    displayOrder: $fieldDef->displayOrder,
                );
                $this->byId[$id] = $stored;
                if (!$stored->isDeleted) {
                    $this->entityTypeFieldKeyToId[$this->compositeKey($stored->entityTypeId, $stored->fieldKey)] = $id;
                }
                $this->nextId = max($this->nextId, $id + 1);
            }
        }
    }

    public function findById(int $id): ?FieldDef
    {
        $fieldDef = $this->byId[$id] ?? null;

        if ($fieldDef === null || $fieldDef->isDeleted) {
            return null;
        }

        return $fieldDef;
    }

    public function findByEntityTypeIdAndFieldKey(int $entityTypeId, string $fieldKey): ?FieldDef
    {
        $id = $this->entityTypeFieldKeyToId[$this->compositeKey($entityTypeId, $fieldKey)] ?? null;

        if ($id === null) {
            return null;
        }

        return $this->findById($id);
    }

    /** @return list<FieldDef> */
    public function findAll(?int $entityTypeId, int $limit, int $offset): array
    {
        $defs = array_values(array_filter(
            $this->byId,
            static fn (FieldDef $fieldDef): bool => !$fieldDef->isDeleted
                && ($entityTypeId === null || $fieldDef->entityTypeId === $entityTypeId),
        ));
        usort(
            $defs,
            static fn (FieldDef $a, FieldDef $b): int => [$a->displayOrder, $a->id ?? 0] <=> [$b->displayOrder, $b->id ?? 0],
        );

        return array_slice($defs, $offset, $limit);
    }

    public function save(FieldDef $fieldDef): int
    {
        $id = $this->nextId++;
        $stored = new FieldDef(
            entityTypeId: $fieldDef->entityTypeId,
            fieldKey: $fieldDef->fieldKey,
            dataType: $fieldDef->dataType,
            id: $id,
            targetEntityTypeId: $fieldDef->targetEntityTypeId,
            cardinality: $fieldDef->cardinality,
            region: $fieldDef->region,
            displayOrder: $fieldDef->displayOrder,
        );
        $this->byId[$id] = $stored;
        $this->entityTypeFieldKeyToId[$this->compositeKey($stored->entityTypeId, $stored->fieldKey)] = $id;

        return $id;
    }

    public function update(FieldDef $fieldDef): void
    {
        $id = $fieldDef->id;

        if ($id === null || !isset($this->byId[$id])) {
            return;
        }

        $old = $this->byId[$id];
        unset($this->entityTypeFieldKeyToId[$this->compositeKey($old->entityTypeId, $old->fieldKey)]);

        $this->entityTypeFieldKeyToId[$this->compositeKey($fieldDef->entityTypeId, $fieldDef->fieldKey)] = $id;
        $this->byId[$id] = $fieldDef;
    }

    public function softDelete(int $id): void
    {
        $fieldDef = $this->byId[$id] ?? null;

        if ($fieldDef === null || $fieldDef->isDeleted) {
            return;
        }

        unset($this->entityTypeFieldKeyToId[$this->compositeKey($fieldDef->entityTypeId, $fieldDef->fieldKey)]);

        $this->byId[$id] = new FieldDef(
            entityTypeId: $fieldDef->entityTypeId,
            fieldKey: $fieldDef->fieldKey,
            dataType: $fieldDef->dataType,
            id: $id,
            isDeleted: true,
            deletedAt: new DateTimeImmutable(),
            targetEntityTypeId: $fieldDef->targetEntityTypeId,
            cardinality: $fieldDef->cardinality,
            region: $fieldDef->region,
            displayOrder: $fieldDef->displayOrder,
        );
    }

    private function compositeKey(int $entityTypeId, string $fieldKey): string
    {
        return $entityTypeId . ':' . $fieldKey;
    }
}
