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
        // The SPA hydrates useEntityTypeList from this payload and never refetches, so
        // every field it reads must ride along — the same rule the entity payload below
        // learned twice (#778 visibility flags, #816 canonical identity). Shipping only
        // id/name/slug left `permalinkPattern` and `defaultLayout` undefined forever:
        // record URLs fell back to the default pattern, and `resolveLayout` fell back to
        // `standard`, painting the themed chrome over a `bare` page until the record
        // itself arrived (#887). Keep this in step with ListEntityTypesHandler, which is
        // what the SPA would have fetched.
        $entityTypesPayload = [
            'items' => array_map(
                static fn (EntityType $item) => [
                    'id' => $item->id,
                    'name' => $item->name,
                    'slug' => $item->slug,
                    'is_pinned' => $item->isPinned,
                    'labels' => $item->labels ?? new \stdClass(),
                    'permalink_pattern' => $item->permalinkPattern,
                    'previous_permalink_pattern' => $item->previousPermalinkPattern,
                    'display_order' => $item->displayOrder,
                    'default_layout' => $item->defaultLayout,
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
                'meta_title' => $entity->metaTitle,
                'meta_description' => $entity->metaDescription,
                // The SPA hydrates useEntity from this payload and never refetches,
                // so the tri-state visibility overrides must ride along (#778).
                'show_comments' => $entity->showComments,
                'show_related' => $entity->showRelated,
                // Canonical URL identity must ride along too (#816): the SPA's
                // useCanonicalRedirect derives the record's URL from entity.permalink
                // (falling back to /{type}/{id} when absent). Without it, a direct
                // load of an explicit-permalink page (e.g. /company/ip, which does
                // not match the type's /{slug} pattern) bounces to /pages/{id} on
                // mount. slug rides along for the title/humanize fallbacks (#657).
                'slug' => $entity->slug,
                'permalink' => $entity->permalink,
                // Per-record layout must ride along too: the SPA resolves the
                // page chrome (standard / full / two-col / bare / custom) from
                // entity.layout, so a `bare`/`custom` custom page can only drop
                // the shell if this payload carries the value it hydrates from.
                'layout' => $entity->layout,
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
