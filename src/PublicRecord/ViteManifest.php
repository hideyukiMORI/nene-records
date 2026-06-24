<?php

declare(strict_types=1);

namespace NeNeRecords\PublicRecord;

/**
 * Resolves the built SPA entry assets from a Vite manifest
 * (`frontend/dist/.vite/manifest.json`) so the single-origin SSR pages can
 * mount the production SPA. Walks the entry's import graph to collect every
 * stylesheet and module-preload, mirroring what Vite injects into its own
 * `index.html`. Returns null when no manifest is available (e.g. unbuilt),
 * letting callers degrade to crawlable-SSR-only.
 *
 * @phpstan-type ViteEntry array{js: string, css: list<string>, preload: list<string>}
 */
final class ViteManifest
{
    /** @var array<string, ViteEntry|null> path => resolved entry (request-lifetime memo) */
    private static array $memo = [];

    /** @return ViteEntry|null */
    public static function resolveEntry(string $manifestPath, string $base = '/'): ?array
    {
        $cacheKey = $manifestPath . '|' . $base;

        if (array_key_exists($cacheKey, self::$memo)) {
            return self::$memo[$cacheKey];
        }

        $resolved = self::load($manifestPath, $base);
        self::$memo[$cacheKey] = $resolved;

        return $resolved;
    }

    /** @return ViteEntry|null */
    private static function load(string $manifestPath, string $base): ?array
    {
        if (!is_file($manifestPath)) {
            return null;
        }

        $raw = file_get_contents($manifestPath);

        if ($raw === false) {
            return null;
        }

        try {
            /** @var array<string, array<string, mixed>> $manifest */
            $manifest = json_decode($raw, true, 32, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return null;
        }

        $entryKey = null;
        foreach ($manifest as $key => $chunk) {
            if (($chunk['isEntry'] ?? false) === true) {
                $entryKey = $key;
                break;
            }
        }

        if ($entryKey === null || !isset($manifest[$entryKey]['file']) || !is_string($manifest[$entryKey]['file'])) {
            return null;
        }

        $css = [];
        $preload = [];
        $seen = [];

        $collect = static function (string $key) use (&$collect, $manifest, &$css, &$preload, &$seen): void {
            if (isset($seen[$key])) {
                return;
            }
            $seen[$key] = true;

            $chunk = $manifest[$key] ?? null;
            if (!is_array($chunk)) {
                return;
            }

            foreach (($chunk['css'] ?? []) as $cssFile) {
                if (is_string($cssFile)) {
                    $css[] = $cssFile;
                }
            }

            foreach (($chunk['imports'] ?? []) as $import) {
                if (!is_string($import)) {
                    continue;
                }
                if (isset($manifest[$import]['file']) && is_string($manifest[$import]['file'])) {
                    $preload[] = $manifest[$import]['file'];
                }
                $collect($import);
            }
        };

        $collect($entryKey);

        $prefix = static fn (string $path): string => rtrim($base, '/') . '/' . ltrim($path, '/');

        return [
            'js' => $prefix((string) $manifest[$entryKey]['file']),
            'css' => array_values(array_unique(array_map($prefix, $css))),
            'preload' => array_values(array_unique(array_map($prefix, $preload))),
        ];
    }
}
