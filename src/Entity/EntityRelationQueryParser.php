<?php

declare(strict_types=1);

namespace NeNeRecords\Entity;

final readonly class EntityRelationQueryParser
{
    private const DOT_PREFIX = 'relation.';

    private const UNDERSCORE_PREFIX = 'relation_';

    /**
     * @param array<string, mixed> $queryParams
     *
     * @return array<string, int>
     */
    public static function parseRelationFilters(array $queryParams): array
    {
        $filters = [];

        foreach ($queryParams as $key => $value) {
            if ($key === 'relation' && is_array($value)) {
                foreach ($value as $fieldKey => $targetValue) {
                    if (!is_string($fieldKey)) {
                        continue;
                    }

                    $targetEntityId = self::parseTargetEntityId($targetValue);

                    if ($targetEntityId !== null) {
                        $filters[$fieldKey] = $targetEntityId;
                    }
                }

                continue;
            }

            if (str_starts_with($key, self::DOT_PREFIX)) {
                $fieldKey = substr($key, strlen(self::DOT_PREFIX));
            } elseif (str_starts_with($key, self::UNDERSCORE_PREFIX)) {
                $fieldKey = substr($key, strlen(self::UNDERSCORE_PREFIX));
            } else {
                continue;
            }

            if ($fieldKey === '') {
                continue;
            }

            $targetEntityId = self::parseTargetEntityId($value);

            if ($targetEntityId !== null) {
                $filters[$fieldKey] = $targetEntityId;
            }
        }

        return $filters;
    }

    private static function parseTargetEntityId(mixed $value): ?int
    {
        if (!is_scalar($value)) {
            return null;
        }

        $raw = trim((string) $value);

        if ($raw === '' || !ctype_digit($raw)) {
            return null;
        }

        $targetEntityId = (int) $raw;

        return $targetEntityId > 0 ? $targetEntityId : null;
    }
}
