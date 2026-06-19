<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\Theme;

use NeNeRecords\Theme\ThemeManifestValidator;
use PHPUnit\Framework\TestCase;

/**
 * Guards that RESERVED_KEYS covers every built-in public theme, so a runtime
 * theme created over MCP can never shadow a built-in id (#458). The source of
 * truth is the set of `[data-theme='id']` blocks under the frontend theme CSS.
 */
final class ThemeReservedKeysSyncTest extends TestCase
{
    public function testReservedKeysMatchBuiltInThemeCss(): void
    {
        $reserved = ThemeManifestValidator::contract()['reservedIds'];
        sort($reserved);

        $builtIn = $this->builtInThemeIdsFromCss();
        self::assertNotEmpty($builtIn, 'No built-in theme CSS found — check the path.');

        self::assertSame(
            $builtIn,
            $reserved,
            'RESERVED_KEYS is out of sync with the built-in theme CSS. '
                . 'Update ThemeManifestValidator::RESERVED_KEYS to match.',
        );
    }

    /** @return list<string> sorted, unique base ids (excludes the -dark variants) */
    private function builtInThemeIdsFromCss(): array
    {
        $dir = dirname(__DIR__, 2) . '/frontend/src/shared/ui/theme/themes';
        $ids = [];
        foreach (glob($dir . '/*.css') ?: [] as $file) {
            $name = basename($file);
            if (str_ends_with($name, '.components.css') || $name === 'admin-themes.css') {
                continue;
            }
            $css = (string) file_get_contents($file);
            if (preg_match_all("/\\[data-theme='([a-z0-9-]+)'\\]\\s*\\{/", $css, $matches) === false) {
                continue;
            }
            foreach ($matches[1] as $id) {
                if (!str_ends_with($id, '-dark')) {
                    $ids[$id] = true;
                }
            }
        }
        $list = array_keys($ids);
        sort($list);

        return $list;
    }
}
