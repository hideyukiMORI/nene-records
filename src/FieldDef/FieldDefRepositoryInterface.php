<?php

declare(strict_types=1);

namespace NeNeRecords\FieldDef;

interface FieldDefRepositoryInterface
{
    public function findById(int $id): ?FieldDef;

    public function findByEntityTypeIdAndFieldKey(int $entityTypeId, string $fieldKey): ?FieldDef;

    /** @return list<FieldDef> */
    public function findAll(?int $entityTypeId, int $limit, int $offset): array;

    public function save(FieldDef $fieldDef): int;

    public function update(FieldDef $fieldDef): void;

    public function softDelete(int $id): void;
}
