<?php

declare(strict_types=1);

namespace NeNeRecords\Setting;

final readonly class SettingHttpMapper
{
    /** @return array<string, mixed> */
    public static function entryToAdminArray(SettingEntry $entry): array
    {
        $stored = $entry->storedValue;

        return [
            'setting_key' => $entry->def->settingKey,
            'label' => $entry->def->label,
            'data_type' => $entry->def->dataType,
            'default_value' => $entry->def->defaultValue,
            'is_public' => $entry->def->isPublic,
            'value' => $entry->effectiveValue,
            'updated_at' => $stored !== null && !$stored->isDeleted ? $stored->updatedAt : null,
        ];
    }

    /** @return array<string, mixed> */
    public static function entryToPublicArray(SettingEntry $entry): array
    {
        return [
            'setting_key' => $entry->def->settingKey,
            'value' => $entry->effectiveValue,
        ];
    }

    /** @return array<string, mixed> */
    public static function valueToArray(SettingValue $value): array
    {
        return [
            'setting_key' => $value->settingKey,
            'value' => $value->value ?? '',
            'updated_at' => $value->updatedAt,
        ];
    }

    /** @return array<string, mixed> */
    public static function revisionToArray(SettingRevision $revision): array
    {
        return [
            'id' => $revision->id,
            'setting_key' => $revision->settingKey,
            'value' => $revision->value,
            'previous_value' => $revision->previousValue,
            'action' => $revision->action,
            'actor_user_id' => $revision->actorUserId,
            'created_at' => $revision->createdAt,
        ];
    }
}
