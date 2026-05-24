<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\PublicRecord;

use NeNeRecords\PublicRecord\PublicMarkdownRenderer;
use PHPUnit\Framework\TestCase;

final class PublicMarkdownRendererTest extends TestCase
{
    public function testRendersHeadingsAndEmphasis(): void
    {
        $html = PublicMarkdownRenderer::toSafeHtml("## Hello\n\n**bold** text");

        self::assertStringContainsString('<h2>Hello</h2>', $html);
        self::assertStringContainsString('<strong>bold</strong>', $html);
    }

    public function testStripsRawHtml(): void
    {
        $html = PublicMarkdownRenderer::toSafeHtml('<script>alert(1)</script>');

        self::assertStringNotContainsString('<script>', $html);
    }

    public function testEmptyMarkdownReturnsEmptyString(): void
    {
        self::assertSame('', PublicMarkdownRenderer::toSafeHtml('   '));
    }
}
