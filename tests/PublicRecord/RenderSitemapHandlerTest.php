<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\PublicRecord;

use NeNeRecords\PublicRecord\GenerateSitemapUseCaseInterface;
use NeNeRecords\PublicRecord\RenderSitemapHandler;
use NeNeRecords\PublicRecord\SitemapUrl;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

final class RenderSitemapHandlerTest extends TestCase
{
    private Psr17Factory $factory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->factory = new Psr17Factory();
    }

    /** A use case backed by a flat list of record paths (global order). */
    private function useCase(int $recordCount): GenerateSitemapUseCaseInterface
    {
        return new class ($recordCount) implements GenerateSitemapUseCaseInterface {
            public function __construct(private int $recordCount)
            {
            }

            public function count(): int
            {
                return $this->recordCount;
            }

            public function page(int $offset, int $limit): array
            {
                $urls = [];
                for ($i = $offset; $i < $offset + $limit && $i < $this->recordCount; $i++) {
                    $urls[] = new SitemapUrl('/posts/' . $i);
                }

                return $urls;
            }
        };
    }

    private function request(string $target): ServerRequestInterface
    {
        return $this->factory->createServerRequest('GET', 'https://x.test' . $target);
    }

    public function testServesSingleUrlsetWhenItFits(): void
    {
        // chunk size 3, total = home + 2 records = 3 → one chunk.
        $handler = new RenderSitemapHandler($this->useCase(2), $this->factory, $this->factory, 3);
        $response = $handler->handle($this->request('/sitemap.xml'));
        $xml = (string) $response->getBody();

        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString('<urlset', $xml);
        self::assertStringNotContainsString('<sitemapindex', $xml);
        self::assertStringContainsString('<loc>https://x.test/</loc>', $xml);
        self::assertStringContainsString('<loc>https://x.test/posts/0</loc>', $xml);
        self::assertStringContainsString('<loc>https://x.test/posts/1</loc>', $xml);
    }

    public function testServesIndexWhenSplit(): void
    {
        // chunk size 2, total = home + 3 records = 4 → ceil(4/2) = 2 chunks.
        $handler = new RenderSitemapHandler($this->useCase(3), $this->factory, $this->factory, 2);
        $xml = (string) $handler->handle($this->request('/sitemap.xml'))->getBody();

        self::assertStringContainsString('<sitemapindex', $xml);
        self::assertStringContainsString('<loc>https://x.test/sitemap.xml?page=1</loc>', $xml);
        self::assertStringContainsString('<loc>https://x.test/sitemap.xml?page=2</loc>', $xml);
        self::assertStringNotContainsString('page=3', $xml);
    }

    public function testPagesPartitionTheGlobalListWithHomeInPageOne(): void
    {
        $handler = new RenderSitemapHandler($this->useCase(3), $this->factory, $this->factory, 2);

        // page 1: home + first record.
        $page1 = (string) $handler->handle($this->request('/sitemap.xml?page=1'))->getBody();
        self::assertStringContainsString('<loc>https://x.test/</loc>', $page1);
        self::assertStringContainsString('<loc>https://x.test/posts/0</loc>', $page1);
        self::assertStringNotContainsString('/posts/1', $page1);

        // page 2: remaining records, no home.
        $page2 = (string) $handler->handle($this->request('/sitemap.xml?page=2'))->getBody();
        self::assertStringNotContainsString('<loc>https://x.test/</loc>', $page2);
        self::assertStringContainsString('<loc>https://x.test/posts/1</loc>', $page2);
        self::assertStringContainsString('<loc>https://x.test/posts/2</loc>', $page2);
    }

    public function testOutOfRangePageReturns404(): void
    {
        $handler = new RenderSitemapHandler($this->useCase(3), $this->factory, $this->factory, 2);

        self::assertSame(404, $handler->handle($this->request('/sitemap.xml?page=3'))->getStatusCode());
        self::assertSame(404, $handler->handle($this->request('/sitemap.xml?page=0'))->getStatusCode());
        self::assertSame(404, $handler->handle($this->request('/sitemap.xml?page=abc'))->getStatusCode());
    }
}
