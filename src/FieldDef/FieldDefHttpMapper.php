<?php

declare(strict_types=1);

namespace NeNeRecords\FieldDef;

final readonly class FieldDefHttpMapper
{
    /**
     * @return array<string, mixed>
     */
    public static function toResponse(
        int $id,
        int $entityTypeId,
        string $fieldKey,
        string $dataType,
        ?int $targetEntityTypeId = null,
        ?string $cardinality = null,
        ?string $region = null,
        int $displayOrder = 0,
    ): array {
        $payload = [
            'id' => $id,
            'entity_type_id' => $entityTypeId,
            'field_key' => $fieldKey,
            'data_type' => $dataType,
            'region' => $region,
            'display_order' => $displayOrder,
        ];

        if ($dataType === 'relation') {
            $payload['target_entity_type_id'] = $targetEntityTypeId;
            $payload['cardinality'] = $cardinality;
        }

        return $payload;
    }

    /**
     * @return array<string, mixed>
     */
    public static function fromFieldDef(FieldDef $fieldDef): array
    {
        return self::toResponse(
            id: $fieldDef->id ?? 0,
            entityTypeId: $fieldDef->entityTypeId,
            fieldKey: $fieldDef->fieldKey,
            dataType: $fieldDef->dataType,
            targetEntityTypeId: $fieldDef->targetEntityTypeId,
            cardinality: $fieldDef->cardinality,
            region: $fieldDef->region,
            displayOrder: $fieldDef->displayOrder,
        );
    }
}
