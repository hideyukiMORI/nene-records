<?php

declare(strict_types=1);

namespace NeNeRecords\Auth;

final class CapabilityResolver
{
    /** @var list<string> */
    private const CONTENT_MUTATION_PREFIXES = [
        '/api/v1/entities',
        '/api/v1/text-fields',
        '/api/v1/int-fields',
        '/api/v1/enum-fields',
        '/api/v1/bool-fields',
        '/api/v1/datetime-fields',
    ];

    public static function resolve(string $path, string $method): ?Capability
    {
        $method = strtoupper($method);

        if (str_starts_with($path, '/api/v1/settings')) {
            return match ($method) {
                'PUT' => Capability::ManageSettings,
                'GET', 'HEAD' => Capability::ReadSettings,
                default => null,
            };
        }

        if (str_starts_with($path, '/api/v1/navigation-items')) {
            return match ($method) {
                'POST', 'PUT', 'DELETE' => Capability::ManageSettings,
                'GET', 'HEAD' => Capability::ReadSettings,
                default => null,
            };
        }

        if (str_starts_with($path, '/api/v1/entity-types')) {
            if (str_contains($path, '/archive.csv')) {
                return Capability::ManageSchema;
            }

            if (self::isMutationMethod($method)) {
                return Capability::ManageSchema;
            }
        }

        if (str_starts_with($path, '/api/v1/field-defs') && self::isMutationMethod($method)) {
            return Capability::ManageSchema;
        }

        if (str_starts_with($path, '/api/v1/tags') && self::isMutationMethod($method)) {
            return Capability::ManageTags;
        }

        if (str_starts_with($path, '/api/v1/media')) {
            return match (true) {
                $method === 'DELETE' => Capability::ManageSettings,
                $method === 'GET' || $method === 'HEAD' => Capability::ReadSettings,
                default => null,
            };
        }

        foreach (self::CONTENT_MUTATION_PREFIXES as $prefix) {
            if (str_starts_with($path, $prefix) && self::isMutationMethod($method)) {
                return Capability::EditContent;
            }
        }

        return null;
    }

    private static function isMutationMethod(string $method): bool
    {
        return !in_array($method, ['GET', 'HEAD', 'OPTIONS'], true);
    }
}
