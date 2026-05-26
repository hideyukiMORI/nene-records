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

        // User management: all mutations require ManageSettings (admin-only)
        // Exception: PUT /api/v1/users/me/password is accessible to any authenticated user (no capability check)
        if (str_starts_with($path, '/api/v1/users') && $path !== '/api/v1/users/me/password' && self::isMutationMethod($method)) {
            return Capability::ManageSettings;
        }

        if (str_starts_with($path, '/api/v1/users') && self::isAdminReadPath($path) && $method === 'GET') {
            return Capability::ManageSettings;
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

    /**
     * Paths under /api/v1/users that expose sensitive user data — admin-only even for GET.
     */
    private static function isAdminReadPath(string $path): bool
    {
        // /api/v1/users (list) and /api/v1/users/{id} (get specific user)
        // Exclude /api/v1/users/me/password which is self-service
        return $path !== '/api/v1/users/me/password';
    }
}
