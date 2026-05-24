<?php

declare(strict_types=1);

namespace NeNeRecords\PublicRecord;

use NeNeRecords\BoolField\BoolField;
use NeNeRecords\DateTimeField\DateTimeField;
use NeNeRecords\Entity\Entity;
use NeNeRecords\EntityType\EntityType;
use NeNeRecords\EnumField\EnumField;
use NeNeRecords\FieldDef\FieldDef;
use NeNeRecords\FieldDef\FieldDefHttpMapper;
use NeNeRecords\IntField\IntField;
use NeNeRecords\TextField\TextField;

final readonly class PublicRecordViewHttpMapper
{
    private const FIELD_DEF_LIMIT = 20;
    private const FIELD_VALUE_LIMIT = 100;
    private const ENTITY_TYPE_LIMIT = 100;

    /**
     * @param list<EntityType> $allEntityTypes
     * @param list<FieldDef> $fieldDefRows
     * @param list<TextField> $textFieldRows
     * @param list<IntField> $intFieldRows
     * @param list<EnumField> $enumFieldRows
     * @param list<BoolField> $boolFieldRows
     * @param list<DateTimeField> $dateTimeFieldRows
     * @param list<array{fieldKey: string, items: list<array{field_key: string, target_entity_id: int}>}> $relationQueries
     * @param array<int, list<TextField>> $relationTextFieldRowsByEntityTypeId
     * @return array<string, mixed>
     */
    public static function toBootstrap(
        string $entityTypeSlug,
        EntityType $entityType,
        Entity $entity,
        array $allEntityTypes,
        array $fieldDefRows,
        array $textFieldRows,
        array $intFieldRows,
        array $enumFieldRows,
        array $boolFieldRows,
        array $dateTimeFieldRows,
        int $entityId,
        int $entityTypeId,
        array $relationQueries,
        array $relationTextFieldRowsByEntityTypeId,
    ): array {
        $entityTypesPayload = [
            'items' => array_map(
                static fn (EntityType $item) => [
                    'id' => $item->id,
                    'name' => $item->name,
                    'slug' => $item->slug,
                ],
                $allEntityTypes,
            ),
            'limit' => self::ENTITY_TYPE_LIMIT,
            'offset' => 0,
        ];

        return [
            'entityTypeSlug' => $entityTypeSlug,
            'entityTypeId' => $entityTypeId,
            'entityId' => $entityId,
            'entityTypes' => $entityTypesPayload,
            'entity' => [
                'id' => $entity->id,
                'entity_type_id' => $entity->entityTypeId,
                'is_deleted' => $entity->isDeleted,
                'deleted_at' => $entity->deletedAt?->format(\DateTimeInterface::ATOM),
            ],
            'fieldDefs' => [
                'items' => array_map(
                    static fn (FieldDef $fieldDef) => FieldDefHttpMapper::fromFieldDef($fieldDef),
                    $fieldDefRows,
                ),
                'limit' => self::FIELD_DEF_LIMIT,
                'offset' => 0,
            ],
            'textFields' => self::textFieldListPayload($textFieldRows),
            'intFields' => self::intFieldListPayload($intFieldRows),
            'enumFields' => self::enumFieldListPayload($enumFieldRows),
            'boolFields' => self::boolFieldListPayload($boolFieldRows),
            'dateTimeFields' => self::dateTimeFieldListPayload($dateTimeFieldRows),
            'entityRelations' => $relationQueries,
            'relationTextFieldsByEntityTypeId' => array_map(
                self::textFieldListPayload(...),
                $relationTextFieldRowsByEntityTypeId,
            ),
        ];
    }

    /**
     * @param list<TextField> $rows
     * @return array{items: list<array<string, mixed>>, limit: int, offset: int}
     */
    private static function textFieldListPayload(array $rows): array
    {
        return [
            'items' => array_map(
                static fn (TextField $item) => [
                    'id' => $item->id,
                    'entity_id' => $item->entityId,
                    'field_key' => $item->fieldKey,
                    'value' => $item->value,
                ],
                $rows,
            ),
            'limit' => self::FIELD_VALUE_LIMIT,
            'offset' => 0,
        ];
    }

    /**
     * @param list<IntField> $rows
     * @return array{items: list<array<string, mixed>>, limit: int, offset: int}
     */
    private static function intFieldListPayload(array $rows): array
    {
        return [
            'items' => array_map(
                static fn (IntField $item) => [
                    'id' => $item->id,
                    'entity_id' => $item->entityId,
                    'field_key' => $item->fieldKey,
                    'value' => $item->value,
                ],
                $rows,
            ),
            'limit' => self::FIELD_VALUE_LIMIT,
            'offset' => 0,
        ];
    }

    /**
     * @param list<EnumField> $rows
     * @return array{items: list<array<string, mixed>>, limit: int, offset: int}
     */
    private static function enumFieldListPayload(array $rows): array
    {
        return [
            'items' => array_map(
                static fn (EnumField $item) => [
                    'id' => $item->id,
                    'entity_id' => $item->entityId,
                    'field_key' => $item->fieldKey,
                    'value' => $item->value,
                ],
                $rows,
            ),
            'limit' => self::FIELD_VALUE_LIMIT,
            'offset' => 0,
        ];
    }

    /**
     * @param list<BoolField> $rows
     * @return array{items: list<array<string, mixed>>, limit: int, offset: int}
     */
    private static function boolFieldListPayload(array $rows): array
    {
        return [
            'items' => array_map(
                static fn (BoolField $item) => [
                    'id' => $item->id,
                    'entity_id' => $item->entityId,
                    'field_key' => $item->fieldKey,
                    'value' => $item->value,
                ],
                $rows,
            ),
            'limit' => self::FIELD_VALUE_LIMIT,
            'offset' => 0,
        ];
    }

    /**
     * @param list<DateTimeField> $rows
     * @return array{items: list<array<string, mixed>>, limit: int, offset: int}
     */
    private static function dateTimeFieldListPayload(array $rows): array
    {
        return [
            'items' => array_map(
                static fn (DateTimeField $item) => [
                    'id' => $item->id,
                    'entity_id' => $item->entityId,
                    'field_key' => $item->fieldKey,
                    'value' => $item->value,
                ],
                $rows,
            ),
            'limit' => self::FIELD_VALUE_LIMIT,
            'offset' => 0,
        ];
    }
}
