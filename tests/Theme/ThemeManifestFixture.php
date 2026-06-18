<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\Theme;

/**
 * Builds a valid runtime theme manifest for tests (all required contract tokens
 * with safe CSS values). Pass overrides to mutate specific fields.
 */
final class ThemeManifestFixture
{
    private const REQUIRED_TOKENS = [
        'color-surface', 'color-surface-raised', 'color-surface-overlay', 'color-surface-sunken',
        'color-text-primary', 'color-text-muted', 'color-text-inverse',
        'color-border', 'color-border-strong', 'color-focus-ring',
        'color-accent', 'color-accent-hover', 'color-accent-weak', 'color-on-accent',
        'color-brand-violet', 'color-danger', 'color-danger-hover',
        'color-ok', 'color-warn', 'color-info',
        'shadow-sm', 'shadow-md', 'shadow-lg', 'color-scheme',
    ];

    /**
     * @param array<string, mixed> $overrides
     *
     * @return array<string, mixed>
     */
    public static function valid(array $overrides = []): array
    {
        $manifest = [
            'id' => 'midnight',
            'name' => 'Midnight',
            'version' => '1.0.0',
            'supportsModes' => ['light', 'dark'],
            'tokens' => [
                'light' => self::tokenSet('light'),
                'dark' => self::tokenSet('dark'),
            ],
            'flags' => ['feedLayout' => 'grid', 'headerSearch' => 'hide'],
        ];

        return array_merge($manifest, $overrides);
    }

    /** @return array<string, string> */
    private static function tokenSet(string $mode): array
    {
        $set = [];
        foreach (self::REQUIRED_TOKENS as $token) {
            $set[$token] = match ($token) {
                'color-scheme' => $mode,
                'shadow-sm', 'shadow-md', 'shadow-lg' => '0 1px 2px rgba(0,0,0,0.1)',
                default => 'oklch(60% 0.1 250)',
            };
        }

        return $set;
    }
}
