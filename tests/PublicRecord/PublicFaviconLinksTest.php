<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\PublicRecord;

use NeNeRecords\PublicRecord\PublicFaviconLinks;
use PHPUnit\Framework\TestCase;

/**
 * The org favicon (#1073).
 *
 * The feature exists because an installation that wanted its own icon previously had to edit
 * `templates/public/*.php` on the server, and that edit — living in no commit — was reverted
 * by every deploy without leaving a trace. So the test that matters most is the fallback one:
 * an org that sets nothing must keep behaving exactly as before.
 */
final class PublicFaviconLinksTest extends TestCase
{
    public function testUnsetOrgKeepsTheProductDefault(): void
    {
        foreach ([[], ['favicon_media_id' => ''], ['favicon_media_id' => '   ']] as $settings) {
            $html = PublicFaviconLinks::render($settings);

            self::assertStringContainsString('assets/favicon/favicon.svg', $html);
            self::assertStringContainsString('assets/favicon/apple-touch-icon.png', $html);
            self::assertStringContainsString('site.webmanifest', $html);
        }
    }

    /**
     * SVG is resolution-independent, so pushing it through the raster derivative pipeline would
     * only lose quality. `MediaDerivativeUrl` agrees — `.svg` is absent from its image extensions.
     */
    public function testSvgIsServedAsUploadedWithoutADerivative(): void
    {
        $html = PublicFaviconLinks::render([
            'favicon_media_id' => 'https://example.test/media/2026/09/brand.svg',
        ]);

        self::assertSame(
            '<link rel="icon" href="https://example.test/media/2026/09/brand.svg" type="image/svg+xml" />',
            $html,
        );
        self::assertStringNotContainsString('/icon32/', $html);
        self::assertStringNotContainsString('assets/favicon/', $html);
    }

    public function testRasterResolvesToTheIconDerivatives(): void
    {
        $html = PublicFaviconLinks::render([
            'favicon_media_id' => 'https://example.test/media/2026/09/brand.png',
        ]);

        self::assertStringContainsString('href="https://example.test/media/icon32/2026/09/brand.png" sizes="32x32"', $html);
        self::assertStringContainsString('href="https://example.test/media/icon192/2026/09/brand.png" sizes="192x192"', $html);
        self::assertStringContainsString('rel="apple-touch-icon" href="https://example.test/media/icon180/2026/09/brand.png"', $html);

        // The product default must be gone, or the browser would pick whichever came first.
        self::assertStringNotContainsString('assets/favicon/', $html);
    }

    /**
     * A value that is not a media-library image (an external URL, a PDF, a typo) must not
     * produce a link that 404s — a broken favicon is the thing this feature prevents.
     */
    public function testUnresolvableValueFallsBackToTheProductDefault(): void
    {
        foreach ([
            'https://example.test/not-media/brand.png',
            'https://example.test/media/2026/09/brochure.pdf',
            'nonsense',
        ] as $value) {
            $html = PublicFaviconLinks::render(['favicon_media_id' => $value]);

            self::assertStringContainsString('assets/favicon/favicon.svg', $html, $value);
        }
    }

    /**
     * Media URLs are root-relative, so unlike the product default's base-relative paths they
     * ignore `<base href>`. A subdirectory install must get the base prefixed explicitly or
     * every icon 404s — the same reasoning og:image already follows.
     */
    public function testSubdirectoryInstallGetsTheBasePrefixed(): void
    {
        $html = PublicFaviconLinks::render(['favicon_media_id' => '/media/2026/06/card.png'], '/sub');

        self::assertStringContainsString('href="/sub/media/icon32/2026/06/card.png"', $html);
        self::assertStringContainsString('href="/sub/media/icon180/2026/06/card.png"', $html);

        // At the domain root the path is emitted unchanged.
        $root = PublicFaviconLinks::render(['favicon_media_id' => '/media/2026/06/card.png']);
        self::assertStringContainsString('href="/media/icon32/2026/06/card.png"', $root);
    }

    public function testValueIsEscapedIntoTheAttribute(): void
    {
        $html = PublicFaviconLinks::render([
            'favicon_media_id' => 'https://example.test/media/2026/09/a" onload="alert(1).svg',
        ]);

        self::assertStringNotContainsString('onload="alert(1)"', $html);
        self::assertStringContainsString('&quot;', $html);
    }
}
