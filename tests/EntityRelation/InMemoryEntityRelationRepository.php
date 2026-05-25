<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\EntityRelation;

use NeNeRecords\EntityRelation\EntityRelationRepositoryInterface;
use NeNeRecords\EntityRelation\ListEntityRelationItem;

final class InMemoryEntityRelationRepository implements EntityRelationRepositoryInterface
{
    /** @var array<string, true> */
    private array $attachments;

    public function __construct()
    {
        $this->attachments = [];
    }

    /** @return list<ListEntityRelationItem> */
    public function findByEntityId(int $entityId): array
    {
        $items = [];

        foreach ($this->attachments as $key => $_attached) {
            [$sourceEntityId, $targetEntityId, $attachedFieldKey] = explode(':', $key, 3);

            if ((int) $sourceEntityId !== $entityId) {
                continue;
            }

            $items[] = new ListEntityRelationItem(
                fieldKey: $attachedFieldKey,
                targetEntityId: (int) $targetEntityId,
            );
        }

        usort(
            $items,
            static fn (ListEntityRelationItem $a, ListEntityRelationItem $b): int =>
                $a->fieldKey <=> $b->fieldKey ?: $a->targetEntityId <=> $b->targetEntityId,
        );

        return $items;
    }

    /** @return list<ListEntityRelationItem> */
    public function findByEntityIdAndFieldKey(int $entityId, string $fieldKey): array
    {
        $items = [];

        foreach ($this->attachments as $key => $_attached) {
            [$sourceEntityId, $targetEntityId, $attachedFieldKey] = explode(':', $key, 3);

            if ((int) $sourceEntityId !== $entityId || $attachedFieldKey !== $fieldKey) {
                continue;
            }

            $items[] = new ListEntityRelationItem(
                fieldKey: $fieldKey,
                targetEntityId: (int) $targetEntityId,
            );
        }

        usort($items, static fn (ListEntityRelationItem $a, ListEntityRelationItem $b): int => $a->targetEntityId <=> $b->targetEntityId);

        return $items;
    }

    public function isAttached(int $sourceEntityId, int $targetEntityId, string $fieldKey): bool
    {
        return isset($this->attachments[$this->compositeKey($sourceEntityId, $targetEntityId, $fieldKey)]);
    }

    public function attach(int $sourceEntityId, int $targetEntityId, string $fieldKey): void
    {
        $this->attachments[$this->compositeKey($sourceEntityId, $targetEntityId, $fieldKey)] = true;
    }

    public function detach(int $sourceEntityId, int $targetEntityId, string $fieldKey): void
    {
        unset($this->attachments[$this->compositeKey($sourceEntityId, $targetEntityId, $fieldKey)]);
    }

    public function detachAllForFieldKey(int $sourceEntityId, string $fieldKey): void
    {
        foreach (array_keys($this->attachments) as $key) {
            [$attachedSourceEntityId, $_targetEntityId, $attachedFieldKey] = explode(':', $key, 3);

            if ((int) $attachedSourceEntityId === $sourceEntityId && $attachedFieldKey === $fieldKey) {
                unset($this->attachments[$key]);
            }
        }
    }

    private function compositeKey(int $sourceEntityId, int $targetEntityId, string $fieldKey): string
    {
        return $sourceEntityId . ':' . $targetEntityId . ':' . $fieldKey;
    }
}
