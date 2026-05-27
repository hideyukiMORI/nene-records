<?php

declare(strict_types=1);

namespace NeNeRecords\Setting;

use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\Http\RequestScopedHolder;

final readonly class PdoSettingRepository implements SettingRepositoryInterface
{
    /**
     * @param RequestScopedHolder<int> $orgId
     */
    public function __construct(
        private DatabaseQueryExecutorInterface $query,
        private readonly RequestScopedHolder $orgId,
    ) {
    }

    /** @return list<SettingDef> */
    public function findAllDefs(): array
    {
        $rows = $this->query->fetchAll(
            'SELECT id, setting_key, data_type, default_value, is_public, label
             FROM setting_defs
             WHERE organization_id = ?
             ORDER BY id ASC',
            [$this->orgId->get()],
        );

        return array_map($this->mapDef(...), $rows);
    }

    public function findDefByKey(string $settingKey): ?SettingDef
    {
        $row = $this->query->fetchOne(
            'SELECT id, setting_key, data_type, default_value, is_public, label
             FROM setting_defs
             WHERE setting_key = ? AND organization_id = ?',
            [$settingKey, $this->orgId->get()],
        );

        return $row === null ? null : $this->mapDef($row);
    }

    public function findValueByKey(string $settingKey): ?SettingValue
    {
        $row = $this->query->fetchOne(
            'SELECT id, setting_key, value, is_deleted, deleted_at, created_by, updated_by, created_at, updated_at
             FROM setting_values
             WHERE setting_key = ? AND organization_id = ?',
            [$settingKey, $this->orgId->get()],
        );

        return $row === null ? null : $this->mapValue($row);
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
        $rows = $this->query->fetchAll(
            'SELECT id, setting_key, value, previous_value, action, actor_user_id, created_at
             FROM setting_revisions
             WHERE setting_key = ?
             ORDER BY id DESC
             LIMIT ? OFFSET ?',
            [$settingKey, $limit, $offset],
        );

        return array_map($this->mapRevision(...), $rows);
    }

    public function applyValue(string $settingKey, string $value, ?int $actorUserId): SettingValue
    {
        $def = $this->findDefByKey($settingKey);

        if ($def === null) {
            throw new SettingKeyNotFoundException($settingKey);
        }

        $normalized = SettingValueValidator::normalize($def->dataType, $value);
        SettingValueValidator::validate($def->dataType, $normalized);

        $existing = $this->findValueByKey($settingKey);
        $previousEffective = $this->resolveEffectiveValue($def, $existing);
        $action = $existing === null || $existing->isDeleted
            ? SettingRevisionAction::Created
            : SettingRevisionAction::Updated;
        $now = date('Y-m-d H:i:s');

        $this->query->execute(
            'INSERT INTO setting_revisions (setting_key, value, previous_value, action, actor_user_id, created_at)
             VALUES (?, ?, ?, ?, ?, ?)',
            [
                $settingKey,
                $normalized,
                $previousEffective === $normalized ? null : $previousEffective,
                $action->value,
                $actorUserId,
                $now,
            ],
        );

        if ($existing === null) {
            $this->query->execute(
                'INSERT INTO setting_values (organization_id, setting_key, value, is_deleted, deleted_at, created_by, updated_by, created_at, updated_at)
                 VALUES (?, ?, ?, 0, NULL, ?, ?, ?, ?)',
                [$this->orgId->get(), $settingKey, $normalized, $actorUserId, $actorUserId, $now, $now],
            );
        } else {
            $this->query->execute(
                'UPDATE setting_values
                 SET value = ?, is_deleted = 0, deleted_at = NULL, updated_by = ?, updated_at = ?
                 WHERE setting_key = ? AND organization_id = ?',
                [$normalized, $actorUserId, $now, $settingKey, $this->orgId->get()],
            );
        }

        $stored = $this->findValueByKey($settingKey);

        if ($stored === null) {
            throw new SettingValueInvalidException('Failed to persist setting value.');
        }

        return $stored;
    }

    /**
     * @param list<SettingDef> $defs
     * @return list<SettingEntry>
     */
    private function buildEntries(array $defs): array
    {
        $allValues = $this->findAllValues();
        $valuesByKey = [];

        foreach ($allValues as $value) {
            $valuesByKey[$value->settingKey] = $value;
        }

        $entries = [];

        foreach ($defs as $def) {
            $stored = $valuesByKey[$def->settingKey] ?? null;
            $entries[] = new SettingEntry(
                def: $def,
                effectiveValue: $this->resolveEffectiveValue($def, $stored),
                storedValue: $stored,
            );
        }

        return $entries;
    }

    /** @return list<SettingValue> */
    private function findAllValues(): array
    {
        $rows = $this->query->fetchAll(
            'SELECT id, setting_key, value, is_deleted, deleted_at, created_by, updated_by, created_at, updated_at
             FROM setting_values
             WHERE organization_id = ?',
            [$this->orgId->get()],
        );

        return array_map($this->mapValue(...), $rows);
    }

    private function resolveEffectiveValue(SettingDef $def, ?SettingValue $stored): string
    {
        if ($stored !== null && !$stored->isDeleted) {
            return $stored->value ?? '';
        }

        return $def->defaultValue ?? '';
    }

    /** @param array<string, mixed> $row */
    private function mapDef(array $row): SettingDef
    {
        return new SettingDef(
            settingKey: (string) $row['setting_key'],
            dataType: (string) $row['data_type'],
            defaultValue: $row['default_value'] !== null ? (string) $row['default_value'] : null,
            isPublic: (bool) $row['is_public'],
            label: (string) $row['label'],
            id: (int) $row['id'],
        );
    }

    /** @param array<string, mixed> $row */
    private function mapValue(array $row): SettingValue
    {
        return new SettingValue(
            settingKey: (string) $row['setting_key'],
            value: $row['value'] !== null ? (string) $row['value'] : null,
            isDeleted: (bool) $row['is_deleted'],
            deletedAt: $row['deleted_at'] !== null ? (string) $row['deleted_at'] : null,
            createdBy: $row['created_by'] !== null ? (int) $row['created_by'] : null,
            updatedBy: $row['updated_by'] !== null ? (int) $row['updated_by'] : null,
            createdAt: (string) $row['created_at'],
            updatedAt: (string) $row['updated_at'],
            id: (int) $row['id'],
        );
    }

    /** @param array<string, mixed> $row */
    private function mapRevision(array $row): SettingRevision
    {
        return new SettingRevision(
            settingKey: (string) $row['setting_key'],
            value: $row['value'] !== null ? (string) $row['value'] : null,
            previousValue: $row['previous_value'] !== null ? (string) $row['previous_value'] : null,
            action: SettingRevisionAction::from((string) $row['action']),
            actorUserId: $row['actor_user_id'] !== null ? (int) $row['actor_user_id'] : null,
            createdAt: (string) $row['created_at'],
            id: (int) $row['id'],
        );
    }
}
