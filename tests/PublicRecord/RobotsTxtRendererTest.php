<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\PublicRecord;

use NeNeRecords\PublicRecord\RobotsTxtRenderer;
use PHPUnit\Framework\TestCase;

final class RobotsTxtRendererTest extends TestCase
{
    public function testAllowsAllAgentsAndAdvertisesSitemap(): void
    {
        $body = RobotsTxtRenderer::render('https://x.test/sitemap.xml');

        self::assertStringContainsString('User-agent: *', $body);
        self::assertStringContainsString('Sitemap: https://x.test/sitemap.xml', $body);
        self::assertStringEndsWith("\n", $body);
    }

    public function testDisallowsBackOfficeButNotApi(): void
    {
        $body = RobotsTxtRenderer::render('https://x.test/sitemap.xml');

        self::assertStringContainsString('Disallow: /admin', $body);
        self::assertStringContainsString('Disallow: /superadmin', $body);
        self::assertStringContainsString('Disallow: /login', $body);
        // /api must stay crawlable so Googlebot can render SPA-shell listing pages.
        self::assertStringNotContainsString('Disallow: /api', $body);
    }
}
