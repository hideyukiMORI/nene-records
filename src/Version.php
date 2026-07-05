<?php

declare(strict_types=1);

namespace NeNeRecords;

/**
 * The application's own release version, surfaced on the auth-gated
 * `GET /machine/health` as the `version` field so NeNe Suite can compare the
 * installed version against Origin's latest and flag updates (#586). This is
 * the *application* version and is intentionally distinct from the NENE2
 * *framework* version (reported separately as `framework_version`).
 *
 * Single source of truth: the root `VERSION` file. `tools/build-release.sh`
 * reads the same file for the release ZIP name, and it is bumped once per
 * release. A missing or blank file yields null, and the framework then simply
 * omits `version` — Suite keeps `unknown` rather than reporting a fabricated
 * value.
 */
final class Version
{
    /**
     * The version string from the VERSION file, or null when it is absent or
     * blank. Pass an explicit $path only in tests; production uses the root file.
     */
    public static function current(?string $path = null): ?string
    {
        $path ??= __DIR__ . '/../VERSION';

        if (!is_file($path)) {
            return null;
        }

        $raw = file_get_contents($path);

        if ($raw === false) {
            return null;
        }

        $version = trim($raw);

        return $version === '' ? null : $version;
    }
}
