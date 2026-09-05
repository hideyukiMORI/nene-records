<?php

declare(strict_types=1);

namespace NeNeRecords\PublicRecord;

use NeNeRecords\Http\BasePath;
use NeNeRecords\Media\MediaDerivativeUrl;

/**
 * The `<link rel="icon">` block for the crawlable SSR shell (#1073).
 *
 * Before this existed the block was hardcoded to the product's own icons, so an installation
 * that wanted its own favicon had no choice but to edit `templates/public/*.php` on the server.
 * ayane.co.jp did exactly that, and the edit lived in no commit — every deploy silently
 * reverted the site to the NeNe Records icon and nothing recorded that it had happened.
 * The org setting replaces that edit, so the templates can ship unmodified.
 *
 * Shape mirrors {@see \NeNeRecords\PublicRecord\WebAnalyticsHeadSnippet} and
 * {@see \NeNeRecords\PublicRecord\FloatingCtaHtml}: the renderer builds a string, the template
 * echoes it. "No config, no change" — an org that has set nothing keeps the product default.
 */
final readonly class PublicFaviconLinks
{
    public const SETTING_KEY = 'favicon_media_id';

    /**
     * Shipped in `dist/assets/favicon` and moved under `public_html/assets/` by the Tier A
     * build. Paths are base-relative so they resolve against `<base>` on any deep path (#986).
     */
    private const PRODUCT_DEFAULT = <<<'HTML'
        <link rel="icon" href="assets/favicon/favicon.svg" type="image/svg+xml" />
            <link rel="icon" href="assets/favicon/favicon-32.png" sizes="32x32" />
            <link rel="icon" href="assets/favicon/favicon-16.png" sizes="16x16" />
            <link rel="apple-touch-icon" href="assets/favicon/apple-touch-icon.png" />
            <link rel="manifest" href="assets/favicon/site.webmanifest" />
        HTML;

    /**
     * Media URLs are root-relative (`/media/...`), which — unlike the product default's
     * base-relative paths — ignore `<base href>`. So a subdirectory install needs the base
     * prefixed explicitly, the same way og:image does.
     *
     * @param array<string, string> $settings   public settings, `settingKey => effectiveValue`
     * @param string                $basePath   effective base path, '' at the domain root
     */
    public static function render(array $settings, string $basePath = ''): string
    {
        $url = trim($settings[self::SETTING_KEY] ?? '');

        if ($url === '') {
            return self::PRODUCT_DEFAULT;
        }

        // SVG is served as uploaded: it is resolution-independent, so rasterising it through
        // the derivative pipeline would only lose quality. `MediaDerivativeUrl` agrees — `.svg`
        // is deliberately absent from its image extensions and it returns null for one.
        if (preg_match('/\.svg$/i', $url) === 1) {
            return '<link rel="icon" href="' . self::escape(self::href($url, $basePath)) . '" type="image/svg+xml" />';
        }

        $small = MediaDerivativeUrl::forPreset($url, 'icon32');
        $apple = MediaDerivativeUrl::forPreset($url, 'icon180');
        $large = MediaDerivativeUrl::forPreset($url, 'icon192');

        // Not a media-library image (an external URL, a PDF, a typo). Falling back to the
        // product icon beats emitting a link that 404s — a broken favicon is the one thing
        // this feature exists to prevent.
        if ($small === null || $apple === null || $large === null) {
            return self::PRODUCT_DEFAULT;
        }

        return '<link rel="icon" href="' . self::escape(self::href($small, $basePath)) . '" sizes="32x32" />' . "\n"
            . '    <link rel="icon" href="' . self::escape(self::href($large, $basePath)) . '" sizes="192x192" />' . "\n"
            . '    <link rel="apple-touch-icon" href="' . self::escape(self::href($apple, $basePath)) . '" />';
    }

    /** Absolute URLs pass through; root-relative media paths get the install's base. */
    private static function href(string $path, string $basePath): string
    {
        return str_starts_with($path, 'http') ? $path : BasePath::prefix($basePath, $path);
    }

    private static function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
