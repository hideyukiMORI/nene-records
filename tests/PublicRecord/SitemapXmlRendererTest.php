<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\PublicRecord;

use NeNeRecords\PublicRecord\SitemapUrl;
use NeNeRecords\PublicRecord\SitemapXmlRenderer;
use PHPUnit\Framework\TestCase;

final class SitemapXmlRendererTest extends TestCase
{
    public function testRendersUrlsetWithAbsoluteLocsAndLastmod(): void
    {
        $xml = SitemapXmlRenderer::render('https://x.test', [
            new SitemapUrl('/', null),
            new SitemapUrl('/posts/1', '2026-02-20T09:00:00+00:00'),
        ]);

        self::assertStringStartsWith('<?xml version="1.0" encoding="UTF-8"?>', $xml);
        self::assertStringContainsString('<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">', $xml);
        self::assertStringContainsString('<url><loc>https://x.test/</loc></url>', $xml);
        self::assertStringContainsString(
            '<url><loc>https://x.test/posts/1</loc><lastmod>2026-02-20T09:00:00+00:00</lastmod></url>',
            $xml,
        );
        self::assertStringContainsString('</urlset>', $xml);
    }

    public function testTrailingSlashOnBaseIsNormalized(): void
    {
        $xml = SitemapXmlRenderer::render('https://x.test/', [new SitemapUrl('/posts/1')]);

        self::assertStringContainsString('<loc>https://x.test/posts/1</loc>', $xml);
        self::assertStringNotContainsString('x.test//posts', $xml);
    }

    public function testEscapesSpecialCharactersInLoc(): void
    {
        $xml = SitemapXmlRenderer::render('https://x.test', [new SitemapUrl('/posts/a&b<c')]);

        self::assertStringContainsString('/posts/a&amp;b&lt;c', $xml);
        self::assertStringNotContainsString('a&b<c', $xml);
    }

    public function testRenderIndexListsChildSitemaps(): void
    {
        $xml = SitemapXmlRenderer::renderIndex('https://x.test', [
            '/sitemap.xml?page=1',
            '/sitemap.xml?page=2',
        ]);

        self::assertStringContainsString('<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">', $xml);
        self::assertStringContainsString('<sitemap><loc>https://x.test/sitemap.xml?page=1</loc></sitemap>', $xml);
        self::assertStringContainsString('<sitemap><loc>https://x.test/sitemap.xml?page=2</loc></sitemap>', $xml);
        self::assertStringContainsString('</sitemapindex>', $xml);
    }
}
