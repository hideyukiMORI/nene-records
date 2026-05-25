<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\Setting;

use NeNeRecords\Setting\SettingDef;
use NeNeRecords\Setting\SettingEntry;
use NeNeRecords\Setting\SettingRepositoryInterface;
use NeNeRecords\Setting\SettingRevision;
use NeNeRecords\Setting\SettingRevisionAction;
use NeNeRecords\Setting\SettingValue;

final class InMemorySettingRepository implements SettingRepositoryInterface
{
    /** @var array<string, SettingDef> */
    private array $defs = [];

    /** @var array<string, SettingValue> */
    private array $values = [];

    /** @var list<SettingRevision> */
    private array $revisions = [];

    private int $revisionId = 0;

    private int $valueId = 0;

    /** @param list<SettingDef> $defs */
    public function __construct(array $defs = [])
    {
        foreach ($defs as $def) {
            $this->defs[$def->settingKey] = $def;
        }
    }

    /** @return list<SettingDef> */
    public function findAllDefs(): array
    {
        return array_values($this->defs);
    }

    public function findDefByKey(string $settingKey): ?SettingDef
    {
        return $this->defs[$settingKey] ?? null;
    }

    public function findValueByKey(string $settingKey): ?SettingValue
    {
        return $this->values[$settingKey] ?? null;
    }

    /** @return list<SettingEntry> */
    public function findAllEntries(): array
    {
        return $this->buildEntries($this->findAllDefs());
    }

    /** @return list<SettingEntry> */
    public function findPublicEntries(): array
    {
        $defs = array_values(array_filter(
            $this->findAllDefs(),
            static fn (SettingDef $def): bool => $def->isPublic,
        ));

        return $this->buildEntries($defs);
    }

    /** @return list<SettingRevision> */
    public function findRevisionsByKey(string $settingKey, int $limit, int $offset): array
    {
        $rows = array_values(array_filter(
            $this->revisions,
            static fn (SettingRevision $revision): bool => $revision->settingKey === $settingKey,
        ));
        usort($rows, static fn (SettingRevision $a, SettingRevision $b): int => ($b->id ?? 0) <=> ($a->id ?? 0));

        return array_slice($rows, $offset, $limit);
    }

    public function applyValue(string $settingKey, string $value, ?int $actorUserId): SettingValue
    {
        return $this->applyValueDirect($settingKey, $value, $actorUserId);
    }

    public function applyValueDirect(string $settingKey, string $value, ?int $actorUserId): SettingValue
    {
        $def = $this->findDefByKey($settingKey);

        if ($def === null) {
            throw new \NeNeRecords\Setting\SettingKeyNotFoundException($settingKey);
        }

        $normalized = \NeNeRecords\Setting\SettingValueValidator::normalize($def->dataType, $value);
        \NeNeRecords\Setting\SettingValueValidator::validate($def->dataType, $normalized);

        $existing = $this->findValueByKey($settingKey);
        $previousEffective = $this->resolveEffectiveValue($def, $existing);
        $action = $existing === null || $existing->isDeleted
            ? SettingRevisionAction::Created
            : SettingRevisionAction::Updated;
        $now = date('Y-m-d H:i:s');

        $this->revisions[] = new SettingRevision(
            settingKey: $settingKey,
            value: $normalized,
            previousValue: $previousEffective === $normalized ? null : $previousEffective,
            action: $action,
            actorUserId: $actorUserId,
            createdAt: $now,
            id: ++$this->revisionId,
        );

        if ($existing === null) {
            $stored = new SettingValue(
                settingKey: $settingKey,
                value: $normalized,
                isDeleted: false,
                deletedAt: null,
                createdBy: $actorUserId,
                updatedBy: $actorUserId,
                createdAt: $now,
                updatedAt: $now,
                id: ++$this->valueId,
            );
            $this->values[$settingKey] = $stored;

            return $stored;
        }

        $stored = new SettingValue(
            settingKey: $settingKey,
            value: $normalized,
            isDeleted: false,
            deletedAt: null,
            createdBy: $existing->createdBy,
            updatedBy: $actorUserId,
            createdAt: $existing->createdAt,
            updatedAt: $now,
            id: $existing->id,
        );
        $this->values[$settingKey] = $stored;

        return $stored;
    }

    /** @param list<SettingDef> $defs
     * @return list<SettingEntry>
     */
    private function buildEntries(array $defs): array
    {
        $entries = [];

        foreach ($defs as $def) {
            $stored = $this->findValueByKey($def->settingKey);
            $entries[] = new SettingEntry(
                def: $def,
                effectiveValue: $this->resolveEffectiveValue($def, $stored),
                storedValue: $stored,
            );
        }

        return $entries;
    }

    private function resolveEffectiveValue(SettingDef $def, ?SettingValue $stored): string
    {
        if ($stored !== null && !$stored->isDeleted) {
            return $stored->value ?? '';
        }

        return $def->defaultValue ?? '';
    }
}
