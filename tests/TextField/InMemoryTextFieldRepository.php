<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\TextField;

use NeNeRecords\Entity\EntityRepositoryInterface;
use NeNeRecords\Entity\EntityStatus;
use NeNeRecords\TextField\TextField;
use NeNeRecords\TextField\TextFieldNotFoundException;
use NeNeRecords\TextField\TextFieldRepositoryInterface;

final class InMemoryTextFieldRepository implements TextFieldRepositoryInterface
{
    /** @var array<int, TextField> */
    private array $fields;

    /** @var array<int, true> */
    private array $deletedIds;

    private int $nextId;

    /**
     * @param list<TextField> $seed
     */
    public function __construct(
        array $seed = [],
        private ?EntityRepositoryInterface $entities = null,
    ) {
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

    public function findById(int $id): ?TextField
    {
        if (isset($this->deletedIds[$id])) {
            return null;
        }

        return $this->fields[$id] ?? null;
    }

    /** @return list<TextField> */
    public function findAll(int $limit, int $offset, ?string $locale = null, bool $publishedOnly = false): array
    {
        $active = [];

        foreach ($this->fields as $id => $textField) {
            if (!isset($this->deletedIds[$id])) {
                if ($locale !== null && $textField->locale !== $locale) {
                    continue;
                }

                if ($publishedOnly && !$this->isPublishedParent($textField->entityId)) {
                    continue;
                }

                $active[] = $textField;
            }
        }

        usort($active, static fn (TextField $a, TextField $b): int => ($a->id ?? 0) <=> ($b->id ?? 0));

        return array_slice($active, $offset, $limit);
    }

    /** @return list<TextField> */
    public function findByEntityId(int $entityId, int $limit, int $offset, ?string $locale = null, bool $publishedOnly = false): array
    {
        if ($publishedOnly && !$this->isPublishedParent($entityId)) {
            return [];
        }

        $active = [];

        foreach ($this->fields as $id => $textField) {
            if (!isset($this->deletedIds[$id]) && $textField->entityId === $entityId) {
                if ($locale !== null && $textField->locale !== $locale) {
                    continue;
                }

                $active[] = $textField;
            }
        }

        usort($active, static fn (TextField $a, TextField $b): int => ($a->id ?? 0) <=> ($b->id ?? 0));

        return array_slice($active, $offset, $limit);
    }

    /** @return list<TextField> */
    public function findByEntityTypeId(int $entityTypeId, int $limit, int $offset, ?string $locale = null, bool $publishedOnly = false): array
    {
        $active = [];

        foreach ($this->fields as $id => $textField) {
            if (isset($this->deletedIds[$id])) {
                continue;
            }

            if ($this->entities === null) {
                continue;
            }

            $entity = $this->entities->findById($textField->entityId);

            if ($entity !== null && $entity->entityTypeId === $entityTypeId) {
                if ($locale !== null && $textField->locale !== $locale) {
                    continue;
                }

                if ($publishedOnly && $entity->status !== EntityStatus::Published) {
                    continue;
                }

                $active[] = $textField;
            }
        }

        usort($active, static fn (TextField $a, TextField $b): int => ($a->id ?? 0) <=> ($b->id ?? 0));

        return array_slice($active, $offset, $limit);
    }

    private function isPublishedParent(int $entityId): bool
    {
        if ($this->entities === null) {
            return false;
        }

        $entity = $this->entities->findById($entityId);

        return $entity !== null && $entity->status === EntityStatus::Published;
    }

    /**
     * @param list<int> $entityIds
     * @return list<TextField>
     */
    public function findByEntityIds(array $entityIds): array
    {
        if ($entityIds === []) {
            return [];
        }

        $active = [];

        foreach ($this->fields as $id => $textField) {
            if (!isset($this->deletedIds[$id]) && in_array($textField->entityId, $entityIds, true)) {
                $active[] = $textField;
            }
        }

        usort($active, static fn (TextField $a, TextField $b): int => ($a->id ?? 0) <=> ($b->id ?? 0));

        return $active;
    }

    public function save(TextField $textField): int
    {
        $id = $this->nextId++;
        $this->fields[$id] = new TextField(
            entityId: $textField->entityId,
            fieldKey: $textField->fieldKey,
            value: $textField->value,
            id: $id,
            locale: $textField->locale,
        );

        return $id;
    }

    public function update(TextField $textField): void
    {
        $id = $textField->id;

        if ($id === null) {
            return;
        }

        if ($this->findById($id) === null) {
            throw new TextFieldNotFoundException($id);
        }

        $this->fields[$id] = $textField;
    }

    public function delete(int $id): void
    {
        if (!isset($this->fields[$id]) || isset($this->deletedIds[$id])) {
            throw new TextFieldNotFoundException($id);
        }

        unset($this->fields[$id]);
        $this->deletedIds[$id] = true;
    }
}
