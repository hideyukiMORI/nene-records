<?php

declare(strict_types=1);

namespace NeNeRecords\Setting;

interface SettingRepositoryInterface
{
    /** @return list<SettingDef> */
    public function findAllDefs(): array;

    public function findDefByKey(string $settingKey): ?SettingDef;

    public function findValueByKey(string $settingKey): ?SettingValue;

    /** @return list<SettingEntry> */
    public function findAllEntries(): array;

    /** @return list<SettingEntry> */
    public function findPublicEntries(): array;

    /** @return list<SettingRevision> */
    public function findRevisionsByKey(string $settingKey, int $limit, int $offset): array;

    public function applyValue(string $settingKey, string $value, ?int $actorUserId): SettingValue;
}
