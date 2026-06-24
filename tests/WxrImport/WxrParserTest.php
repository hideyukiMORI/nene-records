<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\WxrImport;

use NeNeRecords\WxrImport\WxrParseException;
use NeNeRecords\WxrImport\WxrParser;
use PHPUnit\Framework\TestCase;

final class WxrParserTest extends TestCase
{
    private function parseFixture(): \NeNeRecords\WxrImport\WxrDocument
    {
        $xml = file_get_contents(__DIR__ . '/fixtures/sample.wxr.xml');
        self::assertNotFalse($xml);

        return (new WxrParser())->parse($xml);
    }

    public function testParsesChannelMetadataAndTerms(): void
    {
        $doc = $this->parseFixture();

        self::assertSame('My Old Blog', $doc->siteTitle);
        self::assertSame('https://old.example.com', $doc->baseUrl);
        self::assertCount(5, $doc->items);
        self::assertCount(2, $doc->terms);
        self::assertSame('category', $doc->terms[0]->kind);
        self::assertSame('news', $doc->terms[0]->slug);
        self::assertSame('News', $doc->terms[0]->name);
        self::assertSame('tag', $doc->terms[1]->kind);
        self::assertSame('php', $doc->terms[1]->slug);
    }

    public function testParsesPostItemFieldsCategoriesAndDate(): void
    {
        $hello = $this->parseFixture()->items[0];

        self::assertSame(10, $hello->wpPostId);
        self::assertSame('post', $hello->postType);
        self::assertSame('publish', $hello->status);
        self::assertSame('Hello World', $hello->title);
        self::assertSame('hello-world', $hello->slug);
        self::assertStringContainsString('<strong>world</strong>', $hello->contentHtml);
        self::assertSame('A greeting.', $hello->excerptHtml);
        self::assertSame('https://old.example.com/2024/01/hello-world/', $hello->originalLink);
        self::assertSame(['news'], $hello->categorySlugs);
        self::assertSame(['php'], $hello->tagSlugs);
        // WP local date → ISO-8601.
        self::assertNotNull($hello->publishedAtIso);
        self::assertStringStartsWith('2024-01-15T10:00:00', $hello->publishedAtIso);
    }

    public function testItemWithoutSlugYieldsNull(): void
    {
        $noSlug = $this->parseFixture()->items[4];

        self::assertSame('No Slug Here', $noSlug->title);
        self::assertNull($noSlug->slug);
        self::assertSame('post', $noSlug->postType);
    }

    public function testThrowsOnMalformedXml(): void
    {
        $this->expectException(WxrParseException::class);
        (new WxrParser())->parse('<rss><channel><item></broken>');
    }

    public function testThrowsOnEmptyPayload(): void
    {
        $this->expectException(WxrParseException::class);
        (new WxrParser())->parse('   ');
    }
}
