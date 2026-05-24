<?php

declare(strict_types=1);

namespace NeNeRecords\FieldDef;

use Nene2\Validation\ValidationError;

final readonly class FieldDefWriteValidator
{
    /** @var list<string> */
    public const ALLOWED_DATA_TYPES = ['text', 'int', 'enum', 'bool', 'datetime', 'relation'];

    /** @var list<string> */
    public const ALLOWED_CARDINALITIES = ['one', 'many'];

    /**
     * @param array<string, mixed> $body
     * @return list<ValidationError>
     */
    public static function validate(array $body): array
    {
        $errors = [];

        $entityTypeId = (int) ($body['entity_type_id'] ?? 0);
        $fieldKey = trim((string) ($body['field_key'] ?? ''));
        $dataType = trim((string) ($body['data_type'] ?? ''));
        $targetEntityTypeIdRaw = $body['target_entity_type_id'] ?? null;
        $cardinality = trim((string) ($body['cardinality'] ?? ''));

        if ($entityTypeId <= 0) {
            $errors[] = new ValidationError('entity_type_id', 'Entity type id must be a positive integer.', 'invalid');
        }

        if ($fieldKey === '') {
            $errors[] = new ValidationError('field_key', 'Field key is required.', 'required');
        }

        if ($dataType === '') {
            $errors[] = new ValidationError('data_type', 'Data type is required.', 'required');
        } elseif (!in_array($dataType, self::ALLOWED_DATA_TYPES, true)) {
            $errors[] = new ValidationError(
                'data_type',
                'Data type must be one of: text, int, enum, bool, datetime, relation.',
                'invalid',
            );
        }

        if ($dataType === 'relation') {
            $targetEntityTypeId = self::parsePositiveInt($targetEntityTypeIdRaw);

            if ($targetEntityTypeId === null) {
                $errors[] = new ValidationError(
                    'target_entity_type_id',
                    'Target entity type id is required for relation fields.',
                    'required',
                );
            }

            if ($cardinality === '') {
                $errors[] = new ValidationError('cardinality', 'Cardinality is required for relation fields.', 'required');
            } elseif (!in_array($cardinality, self::ALLOWED_CARDINALITIES, true)) {
                $errors[] = new ValidationError('cardinality', 'Cardinality must be one of: one, many.', 'invalid');
            }
        } elseif ($targetEntityTypeIdRaw !== null || $cardinality !== '') {
            $errors[] = new ValidationError(
                'data_type',
                'Target entity type id and cardinality are only allowed for relation fields.',
                'invalid',
            );
        }

        return $errors;
    }

    /**
     * @param array<string, mixed> $body
     */
    public static function parseTargetEntityTypeId(array $body): ?int
    {
        if (trim((string) ($body['data_type'] ?? '')) !== 'relation') {
            return null;
        }

        return self::parsePositiveInt($body['target_entity_type_id'] ?? null);
    }

    /**
     * @param array<string, mixed> $body
     */
    public static function parseCardinality(array $body): ?string
    {
        if (trim((string) ($body['data_type'] ?? '')) !== 'relation') {
            return null;
        }

        $cardinality = trim((string) ($body['cardinality'] ?? ''));

        return $cardinality === '' ? null : $cardinality;
    }

    private static function parsePositiveInt(mixed $value): ?int
    {
        if (!is_int($value) && !(is_string($value) && ctype_digit($value))) {
            return null;
        }

        $parsed = (int) $value;

        return $parsed > 0 ? $parsed : null;
    }
}
